<?php
namespace Predis;

class PredisException extends \Exception { }
class ClientException extends PredisException { }                   // Client-side errors
class AbortedMultiExec extends PredisException { }                  // Aborted multi/exec

class ServerException extends PredisException {                     // Server-side errors
    public function toResponseError() {
        return new ResponseError($this->getMessage());
    }
}

class CommunicationException extends PredisException {              // Communication errors
    private $_connection;

    public function __construct(Connection $connection, $message = null, $code = null) {
        $this->_connection = $connection;
        parent::__construct($message, $code);
    }

    public function getConnection() { return $this->_connection; }
    public function shouldResetConnection() {  return true; }
}

class MalformedServerResponse extends CommunicationException { }    // Unexpected responses

/* ------------------------------------------------------------------------- */

class Client {
    private $_options, $_connection, $_serverProfile, $_responseReader;

    public function __construct($parameters = null, $clientOptions = null) {
        $this->setupClient($clientOptions ?: new ClientOptions());
        $this->setupConnection($parameters);
    }

    public static function create(/* arguments */) {
        $argv = func_get_args();
        $argc = func_num_args();

        $options = null;
        $lastArg = $argv[$argc-1];
        if ($argc > 0 && !is_string($lastArg) && ($lastArg instanceof ClientOptions ||
            is_subclass_of($lastArg, '\Predis\RedisServerProfile'))) {
            $options = array_pop($argv);
            $argc--;
        }

        if ($argc === 0) {
            throw new ClientException('Missing connection parameters');
        }

        return new Client($argc === 1 ? $argv[0] : $argv, $options);
    }

    private static function filterClientOptions($options) {
        if ($options instanceof ClientOptions) {
            return $options;
        }
        if (is_array($options)) {
            return new ClientOptions($options);
        }
        if ($options instanceof RedisServerProfile) {
            return new ClientOptions(array(
                'profile' => $options
            ));
        }
        if (is_string($options)) {
            return new ClientOptions(array(
                'profile' => RedisServerProfile::get($options)
            ));
        }
        throw new \InvalidArgumentException("Invalid type for client options");
    }

    private function setupClient($options) {
        $this->_responseReader = new ResponseReader();
        $this->_options = self::filterClientOptions($options);

        $this->setProfile($this->_options->profile);
        if ($this->_options->iterable_multibulk === true) {
            $this->_responseReader->setHandler(
                Protocol::PREFIX_MULTI_BULK, 
                new ResponseMultiBulkStreamHandler()
            );
        }
        if ($this->_options->throw_on_error === false) {
            $this->_responseReader->setHandler(
                Protocol::PREFIX_ERROR, 
                new ResponseErrorSilentHandler()
            );
        }
    }

    private function setupConnection($parameters) {
        if ($parameters !== null && !(is_array($parameters) || is_string($parameters))) {
            throw new ClientException('Invalid parameters type (array or string expected)');
        }

        if (is_array($parameters) && isset($parameters[0])) {
            $cluster = new ConnectionCluster($this->_options->key_distribution);
            foreach ($parameters as $shardParams) {
                $cluster->add($this->createConnection($shardParams));
            }
            $this->setConnection($cluster);
        }
        else {
            $this->setConnection($this->createConnection($parameters));
        }
    }

    private function createConnection($parameters) {
        $params     = $parameters instanceof ConnectionParameters 
                          ? $parameters 
                          : new ConnectionParameters($parameters);
        $connection = new Connection($params, $this->_responseReader);

        if ($params->password !== null) {
            $connection->pushInitCommand($this->createCommand(
                'auth', array($params->password)
            ));
        }
        if ($params->database !== null) {
            $connection->pushInitCommand($this->createCommand(
                'select', array($params->database)
            ));
        }

        return $connection;
    }

    private function setConnection(IConnection $connection) {
        $this->_connection = $connection;
    }

    public function setProfile($serverProfile) {
        if (!($serverProfile instanceof RedisServerProfile || is_string($serverProfile))) {
            throw new \InvalidArgumentException(
                "Invalid type for server profile, \Predis\RedisServerProfile or string expected"
            );
        }
        $this->_serverProfile = (is_string($serverProfile) 
            ? RedisServerProfile::get($serverProfile)
            : $serverProfile
        );
    }

    public function getProfile() {
        return $this->_serverProfile;
    }

    public function getResponseReader() {
        return $this->_responseReader;
    }

    public function getClientFor($connectionAlias) {
        if (!Shared\Utils::isCluster($this->_connection)) {
            throw new ClientException(
                'This method is supported only when the client is connected to a cluster of connections'
            );
        }

        $connection = $this->_connection->getConnectionById($connectionAlias);
        if ($connection === null) {
            throw new \InvalidArgumentException(
                "Invalid connection alias: '$connectionAlias'"
            );
        }

        $newClient = new Client();
        $newClient->setupClient($this->_options);
        $newClient->setConnection($connection);
        return $newClient;
    }

    public function connect() {
        $this->_connection->connect();
    }

    public function disconnect() {
        $this->_connection->disconnect();
    }

    public function isConnected() {
        return $this->_connection->isConnected();
    }

    public function getConnection($id = null) {
        if (!isset($id)) {
            return $this->_connection;
        }
        else {
            return Shared\Utils::isCluster($this->_connection)
                ? $this->_connection->getConnectionById($id)
                : $this->_connection;
        }
    }

    public function __call($method, $arguments) {
        $command = $this->_serverProfile->createCommand($method, $arguments);
        return $this->_connection->executeCommand($command);
    }

    public function createCommand($method, $arguments = array()) {
        return $this->_serverProfile->createCommand($method, $arguments);
    }

    public function executeCommand(Command $command) {
        return $this->_connection->executeCommand($command);
    }

    public function executeCommandOnShards(Command $command) {
        $replies = array();
        if (Shared\Utils::isCluster($this->_connection)) {
            foreach($this->_connection as $connection) {
                $replies[] = $connection->executeCommand($command);
            }
        }
        else {
            $replies[] = $this->_connection->executeCommand($command);
        }
        return $replies;
    }

    public function rawCommand($rawCommandData, $closesConnection = false) {
        if (Shared\Utils::isCluster($this->_connection)) {
            throw new ClientException('Cannot send raw commands when connected to a cluster of Redis servers');
        }
        return $this->_connection->rawCommand($rawCommandData, $closesConnection);
    }

    private function sharedInitializer($argv, $initializer) {
        $argc = count($argv);
        if ($argc === 0) {
            return $this->$initializer();
        }
        else if ($argc === 1) {
            list($arg0) = $argv;
            return is_array($arg0) ? $this->$initializer($arg0) : $this->$initializer(null, $arg0);
        }
        else if ($argc === 2) {
            list($arg0, $arg1) = $argv;
            return $this->$initializer($arg0, $arg1);
        }
        return $this->$initializer($this, $arguments);
    }

    public function pipeline(/* arguments */) {
        return $this->sharedInitializer(func_get_args(), 'initPipeline');
    }

    public function pipelineSafe($pipelineBlock = null) {
        return $this->initPipeline(array('safe' => true), $pipelineBlock);
    }

    private function initPipeline(Array $options = null, $pipelineBlock = null) {
        $pipeline = null;
        if (isset($options)) {
            if (isset($options['safe']) && $options['safe'] == true) {
                $connection = $this->getConnection();
                $pipeline   = new CommandPipeline($this, $connection instanceof Connection
                    ? new Pipeline\SafeExecutor($connection)
                    : new Pipeline\SafeClusterExecutor($connection)
                );
            }
            else {
                $pipeline = new CommandPipeline($this);
            }
        }
        else {
            $pipeline = new CommandPipeline($this);
        }
        return $this->pipelineExecute($pipeline, $pipelineBlock);
    }

    private function pipelineExecute(CommandPipeline $pipeline, $block) {
        return $block !== null ? $pipeline->execute($block) : $pipeline;
    }

    public function multiExec(/* arguments */) {
        return $this->sharedInitializer(func_get_args(), 'initMultiExec');
    }

    private function initMultiExec(Array $options = null, $transBlock = null) {
        $multi = isset($options) ? new MultiExecBlock($this, $options) : new MultiExecBlock($this);
        return $transBlock !== null ? $multi->execute($transBlock) : $multi;
    }

    public function pubSubContext(Array $options = null) {
        return new PubSubContext($this, $options);
    }
}

/* ------------------------------------------------------------------------- */

interface IClientOptionsHandler {
    public function validate($option, $value);
    public function getDefault();
}

class ClientOptionsProfile implements IClientOptionsHandler {
    public function validate($option, $value) {
        if ($value instanceof \Predis\RedisServerProfile) {
            return $value;
        }
        if (is_string($value)) {
            return \Predis\RedisServerProfile::get($value);
        }
        throw new \InvalidArgumentException("Invalid value for option $option");
    }

    public function getDefault() {
        return \Predis\RedisServerProfile::getDefault();
    }
}

class ClientOptionsKeyDistribution implements IClientOptionsHandler {
    public function validate($option, $value) {
        if ($value instanceof \Predis\Distribution\IDistributionStrategy) {
            return $value;
        }
        if (is_string($value)) {
            $valueReflection = new \ReflectionClass($value);
            if ($valueReflection->isSubclassOf('\Predis\Distribution\IDistributionStrategy')) {
                return new $value;
            }
        }
        throw new \InvalidArgumentException("Invalid value for option $option");
    }

    public function getDefault() {
        return new \Predis\Distribution\HashRing();
    }
}

class ClientOptionsIterableMultiBulk implements IClientOptionsHandler {
    public function validate($option, $value) {
        return (bool) $value;
    }

    public function getDefault() {
        return false;
    }
}

class ClientOptionsThrowOnError implements IClientOptionsHandler {
    public function validate($option, $value) {
        return (bool) $value;
    }

    public function getDefault() {
        return true;
    }
}

class ClientOptions {
    private static $_optionsHandlers;
    private $_options;

    public function __construct($options = null) {
        self::initializeOptionsHandlers();
        $this->initializeOptions($options ?: array());
    }

    private static function initializeOptionsHandlers() {
        if (!isset(self::$_optionsHandlers)) {
            self::$_optionsHandlers = self::getOptionsHandlers();
        }
    }

    private static function getOptionsHandlers() {
        return array(
            'profile'    => new \Predis\ClientOptionsProfile(),
            'key_distribution' => new \Predis\ClientOptionsKeyDistribution(),
            'iterable_multibulk' => new \Predis\ClientOptionsIterableMultiBulk(),
            'throw_on_error' => new \Predis\ClientOptionsThrowOnError(),
        );
    }

    private function initializeOptions($options) {
        foreach ($options as $option => $value) {
            if (isset(self::$_optionsHandlers[$option])) {
                $handler = self::$_optionsHandlers[$option];
                $this->_options[$option] = $handler->validate($option, $value);
            }
        }
    }

    public function __get($option) {
        if (!isset($this->_options[$option])) {
            $defaultValue = self::$_optionsHandlers[$option]->getDefault();
            $this->_options[$option] = $defaultValue;
        }
        return $this->_options[$option];
    }

    public function __isset($option) {
        return isset(self::$_optionsHandlers[$option]);
    }
}

/* ------------------------------------------------------------------------- */

class Protocol {
    const NEWLINE = "\r\n";
    const OK      = 'OK';
    const ERROR   = 'ERR';
    const QUEUED  = 'QUEUED';
    const NULL    = 'nil';

    const PREFIX_STATUS     = '+';
    const PREFIX_ERROR      = '-';
    const PREFIX_INTEGER    = ':';
    const PREFIX_BULK       = '$';
    const PREFIX_MULTI_BULK = '*';
}

abstract class Command {
    private $_hash;
    private $_arguments = array();

    public abstract function getCommandId();

    public abstract function serializeRequest($command, $arguments);

    public function canBeHashed() {
        return true;
    }

    public function getHash(Distribution\IDistributionStrategy $distributor) {
        if (isset($this->_hash)) {
            return $this->_hash;
        }

        if (isset($this->_arguments[0])) {
            // TODO: should we throw an exception if the command does 
            //       not support sharding?
            $key = $this->_arguments[0];

            $start = strpos($key, '{');
            if ($start !== false) {
                $end = strpos($key, '}', $start);
                if ($end !== false) {
                    $key = substr($key, ++$start, $end - $start);
                }
            }

            $this->_hash = $distributor->generateKey($key);
            return $this->_hash;
        }

        return null;
    }

    public function closesConnection() {
        return false;
    }

    protected function filterArguments(Array $arguments) {
        return $arguments;
    }

    public function setArguments(/* arguments */) {
        $this->_arguments = $this->filterArguments(func_get_args());
        unset($this->_hash);
    }

    public function setArgumentsArray(Array $arguments) {
        $this->_arguments = $this->filterArguments($arguments);
        unset($this->_hash);
    }

    public function getArguments() {
        return $this->_arguments;
    }

    public function getArgument($index = 0) {
        if (isset($this->_arguments[$index]) === true) {
            return $this->_arguments[$index];
        }
    }

    public function parseResponse($data) {
        return $data;
    }

    public final function __invoke() {
        return $this->serializeRequest($this->getCommandId(), $this->getArguments());
    }
}

abstract class InlineCommand extends Command {
    public function serializeRequest($command, $arguments) {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            $arguments[0] = implode($arguments[0], ' ');
        }
        return $command . (count($arguments) > 0
            ? ' ' . implode($arguments, ' ') . "\r\n" : "\r\n"
        );
    }
}

abstract class BulkCommand extends Command {
    public function serializeRequest($command, $arguments) {
        $data = array_pop($arguments);
        if (is_array($data)) {
            $data = implode($data, ' ');
        }
        return $command . ' ' . implode($arguments, ' ') . ' ' . strlen($data) . 
            "\r\n" . $data . "\r\n";
    }
}

abstract class MultiBulkCommand extends Command {
    public function serializeRequest($command, $arguments) {
        $cmd_args = null;
        $argsc    = count($arguments);

        if ($argsc === 1 && is_array($arguments[0])) {
            $cmd_args = $arguments[0];
            $argsc = count($cmd_args);
        }
        else {
            $cmd_args = $arguments;
        }

        $cmdlen  = strlen($command);
        $reqlen  = $argsc + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$command}\r\n";
        for ($i = 0; $i < $reqlen - 1; $i++) {
            $argument = $cmd_args[$i];
            $arglen  = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        return $buffer;
    }
}

/* ------------------------------------------------------------------------- */

interface IResponseHandler {
    function handle(Connection $connection, $payload);
}

class ResponseStatusHandler implements IResponseHandler {
    public function handle(Connection $connection, $status) {
        if ($status === 'OK') {
            return true;
        }
        if ($status === 'QUEUED') {
            return new ResponseQueued();
        }
        return $status;
    }
}

class ResponseErrorHandler implements IResponseHandler {
    public function handle(Connection $connection, $errorMessage) {
        throw new ServerException(substr($errorMessage, 4));
    }
}

class ResponseErrorSilentHandler implements IResponseHandler {
    public function handle(Connection $connection, $errorMessage) {
        return new ResponseError(substr($errorMessage, 4));
    }
}

class ResponseBulkHandler implements IResponseHandler {
    public function handle(Connection $connection, $lengthString) {
        $length = (int) $lengthString;
        if ($length != $lengthString) {
            Shared\Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$length' as data length"
            ));
        }
        if ($length >= 0) {
            return substr($connection->readBytes($length + 2), 0, -2);
        }
        if ($length == -1) {
            return null;
        }
    }
}

class ResponseMultiBulkHandler implements IResponseHandler {
    public function handle(Connection $connection, $lengthString) {
        $listLength = (int) $lengthString;
        if ($listLength != $lengthString) {
            Shared\Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$lengthString' as data length"
            ));
        }

        if ($listLength === -1) {
            return null;
        }

        $list = array();

        if ($listLength > 0) {
            $handlers = array();
            $reader = $connection->getResponseReader();
            for ($i = 0; $i < $listLength; $i++) {
                $header = $connection->readLine();
                $prefix = $header[0];
                if (isset($handlers[$prefix])) {
                    $handler = $handlers[$prefix];
                }
                else {
                    $handler = $reader->getHandler($prefix);
                    $handlers[$prefix] = $handler;
                }
                $list[$i] = $handler->handle($connection, substr($header, 1));
            }
        }

        return $list;
    }
}

class ResponseMultiBulkStreamHandler implements IResponseHandler {
    public function handle(Connection $connection, $lengthString) {
        $listLength = (int) $lengthString;
        if ($listLength != $lengthString) {
            Shared\Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$lengthString' as data length"
            ));
        }
        return new Shared\MultiBulkResponseIterator($connection, $lengthString);
    }
}

class ResponseIntegerHandler implements IResponseHandler {
    public function handle(Connection $connection, $number) {
        if (is_numeric($number)) {
            return (int) $number;
        }
        if ($number !== 'nil') {
            Shared\Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$number' as numeric response"
            ));
        }
        return null;
    }
}

class ResponseReader {
    private $_prefixHandlers;

    public function __construct() {
        $this->initializePrefixHandlers();
    }

    private function initializePrefixHandlers() {
        $this->_prefixHandlers = array(
            Protocol::PREFIX_STATUS     => new ResponseStatusHandler(), 
            Protocol::PREFIX_ERROR      => new ResponseErrorHandler(), 
            Protocol::PREFIX_INTEGER    => new ResponseIntegerHandler(), 
            Protocol::PREFIX_BULK       => new ResponseBulkHandler(), 
            Protocol::PREFIX_MULTI_BULK => new ResponseMultiBulkHandler(), 
        );
    }

    public function setHandler($prefix, IResponseHandler $handler) {
        $this->_prefixHandlers[$prefix] = $handler;
    }

    public function getHandler($prefix) {
        if (isset($this->_prefixHandlers[$prefix])) {
            return $this->_prefixHandlers[$prefix];
        }
    }

    public function read(Connection $connection) {
        $header = $connection->readLine();
        if ($header === '') {
            $this->throwMalformedResponse($connection, 'Unexpected empty header');
        }

        $prefix  = $header[0];
        if (!isset($this->_prefixHandlers[$prefix])) {
            $this->throwMalformedResponse($connection, "Unknown prefix '$prefix'");
        }
        $handler = $this->_prefixHandlers[$prefix];
        return $handler->handle($connection, substr($header, 1));
    }

    private function throwMalformedResponse(Connection $connection, $message) {
        Shared\Utils::onCommunicationException(new MalformedServerResponse(
            $connection, $message
        ));
    }
}

class ResponseError {
    public $skipParse = true;
    private $_message;

    public function __construct($message) {
        $this->_message = $message;
    }

    public function __get($property) {
        if ($property === 'error') {
            return true;
        }
        if ($property === 'message') {
            return $this->_message;
        }
    }

    public function __isset($property) {
        return $property === 'error';
    }

    public function __toString() {
        return $this->_message;
    }
}

class ResponseQueued {
    public $skipParse = true;

    public function __toString() {
        return Protocol::QUEUED;
    }

    public function __get($property) {
        if ($property === 'queued') {
            return true;
        }
    }

    public function __isset($property) {
        return $property === 'queued';
    }
}

/* ------------------------------------------------------------------------- */

class CommandPipeline {
    private $_redisClient, $_pipelineBuffer, $_returnValues, $_running, $_executor;

    public function __construct(Client $redisClient, Pipeline\IPipelineExecutor $executor = null) {
        $this->_redisClient    = $redisClient;
        $this->_executor       = $executor ?: new Pipeline\StandardExecutor();
        $this->_pipelineBuffer = array();
        $this->_returnValues   = array();
    }

    public function __call($method, $arguments) {
        $command = $this->_redisClient->createCommand($method, $arguments);
        $this->recordCommand($command);
        return $this;
    }

    private function recordCommand(Command $command) {
        $this->_pipelineBuffer[] = $command;
    }

    private function getRecordedCommands() {
        return $this->_pipelineBuffer;
    }

    public function flushPipeline() {
        if (count($this->_pipelineBuffer) > 0) {
            $connection = $this->_redisClient->getConnection();
            $this->_returnValues = array_merge(
                $this->_returnValues, 
                $this->_executor->execute($connection, $this->_pipelineBuffer)
            );
            $this->_pipelineBuffer = array();
        }
        return $this;
    }

    private function setRunning($bool) {
        if ($bool == true && $this->_running == true) {
            throw new ClientException("This pipeline is already opened");
        }
        $this->_running = $bool;
    }

    public function execute($block = null) {
        if ($block && !is_callable($block)) {
            throw new \InvalidArgumentException('Argument passed must be a callable object');
        }

        // TODO: do not reuse previously executed pipelines
        $this->setRunning(true);
        $pipelineBlockException = null;

        try {
            if ($block !== null) {
                $block($this);
            }
            $this->flushPipeline();
        }
        catch (\Exception $exception) {
            $pipelineBlockException = $exception;
        }

        $this->setRunning(false);

        if ($pipelineBlockException !== null) {
            throw $pipelineBlockException;
        }

        return $this->_returnValues;
    }
}

class MultiExecBlock {
    private $_initialized, $_discarded, $_insideBlock, $_checkAndSet;
    private $_redisClient, $_options, $_commands;
    private $_supportsWatch;

    public function __construct(Client $redisClient, Array $options = null) {
        $this->checkCapabilities($redisClient);
        $this->_options = $options ?: array();
        $this->_redisClient = $redisClient;
        $this->reset();
    }

    private function checkCapabilities(Client $redisClient) {
        if (Shared\Utils::isCluster($redisClient->getConnection())) {
            throw new \Predis\ClientException(
                'Cannot initialize a MULTI/EXEC context over a cluster of connections'
            );
        }
        $profile = $redisClient->getProfile();
        if ($profile->supportsCommands(array('multi', 'exec', 'discard')) === false) {
            throw new \Predis\ClientException(
                'The current profile does not support MULTI, EXEC and DISCARD commands'
            );
        }
        $this->_supportsWatch = $profile->supportsCommands(array('watch', 'unwatch'));
    }

    private function isWatchSupported() {
        if ($this->_supportsWatch === false) {
            throw new \Predis\ClientException(
                'The current profile does not support WATCH and UNWATCH commands'
            );
        }
    }

    private function reset() {
        $this->_initialized = false;
        $this->_discarded   = false;
        $this->_checkAndSet = false;
        $this->_insideBlock = false;
        $this->_commands    = array();
    }

    private function initialize() {
        if ($this->_initialized === true) {
            return;
        }
        $options = &$this->_options;
        $this->_checkAndSet = isset($options['cas']) && $options['cas'];
        if (isset($options['watch'])) {
            $this->watch($options['watch']);
        }
        if (!$this->_checkAndSet || ($this->_discarded && $this->_checkAndSet)) {
            $this->_redisClient->multi();
            if ($this->_discarded) {
                $this->_checkAndSet = false;
            }
        }
        $this->_initialized = true;
        $this->_discarded   = false;
    }

    public function __call($method, $arguments) {
        $this->initialize();
        $client = $this->_redisClient;
        if ($this->_checkAndSet) {
            return call_user_func_array(array($client, $method), $arguments);
        }
        $command  = $client->createCommand($method, $arguments);
        $response = $client->executeCommand($command);
        if (!$response instanceof \Predis\ResponseQueued) {
            $this->malformedServerResponse(
                'The server did not respond with a QUEUED status reply'
            );
        }
        $this->_commands[] = $command;
        return $this;
    }

    public function watch($keys) {
        $this->isWatchSupported();
        if ($this->_initialized && !$this->_checkAndSet) {
            throw new ClientException('WATCH inside MULTI is not allowed');
        }
        return $this->_redisClient->watch($keys);
    }

    public function multi() {
        if ($this->_initialized && $this->_checkAndSet) {
            $this->_checkAndSet = false;
            $this->_redisClient->multi();
            return $this;
        }
        $this->initialize();
        return $this;
    }

    public function unwatch() {
        $this->isWatchSupported();
        $this->_redisClient->unwatch();
        return $this;
    }

    public function discard() {
        $this->_redisClient->discard();
        $this->reset();
        $this->_discarded = true;
        return $this;
    }

    public function exec() {
        return $this->execute();
    }

    private function checkBeforeExecution($block) {
        if ($this->_insideBlock === true) {
            throw new \Predis\ClientException(
                "Cannot invoke 'execute' or 'exec' inside an active client transaction block"
            );
        }
        if ($block) {
            if (!is_callable($block)) {
                throw new \InvalidArgumentException(
                    'Argument passed must be a callable object'
                );
            }
            if (count($this->_commands) > 0) {
                throw new ClientException(
                    'Cannot execute a transaction block after using fluent interface'
                );
            }
        }
        if (isset($this->_options['retry']) && !isset($block)) {
            $this->discard();
            throw new \InvalidArgumentException(
                'Automatic retries can be used only when a transaction block is provided'
            );
        }
    }

    public function execute($block = null) {
        $this->checkBeforeExecution($block);

        $reply = null;
        $returnValues = array();
        $attemptsLeft = isset($this->_options['retry']) ? (int)$this->_options['retry'] : 0;
        do {
            $blockException = null;
            if ($block !== null) {
                $this->_insideBlock = true;
                try {
                    $block($this);
                }
                catch (CommunicationException $exception) {
                    $blockException = $exception;
                }
                catch (ServerException $exception) {
                    $blockException = $exception;
                }
                catch (\Exception $exception) {
                    $blockException = $exception;
                    if ($this->_initialized === true) {
                        $this->discard();
                    }
                }
                $this->_insideBlock = false;
                if ($blockException !== null) {
                    throw $blockException;
                }
            }

            if ($this->_initialized === false || count($this->_commands) == 0) {
                return;
            }

            $reply = $this->_redisClient->exec();
            if ($reply === null) {
                if ($attemptsLeft === 0) {
                    throw new AbortedMultiExec(
                        'The current transaction has been aborted by the server'
                    );
                }
                $this->reset();
                if (isset($this->_options['on_retry']) && is_callable($this->_options['on_retry'])) {
                    call_user_func($this->_options['on_retry'], $this, $attemptsLeft);
                }
                continue;
            }
            break;
        } while ($attemptsLeft-- > 0);

        $execReply = $reply instanceof \Iterator ? iterator_to_array($reply) : $reply;
        $sizeofReplies = count($execReply);

        $commands = &$this->_commands;
        if ($sizeofReplies !== count($commands)) {
            $this->malformedServerResponse(
                'Unexpected number of responses for a MultiExecBlock'
            );
        }
        for ($i = 0; $i < $sizeofReplies; $i++) {
            $returnValues[] = $commands[$i]->parseResponse($execReply[$i] instanceof \Iterator
                ? iterator_to_array($execReply[$i])
                : $execReply[$i]
            );
            unset($commands[$i]);
        }

        return $returnValues;
    }

    private function malformedServerResponse($message) {
        // Since a MULTI/EXEC block cannot be initialized over a clustered 
        // connection, we can safely assume that Predis\Client::getConnection() 
        // will always return an instance of Predis\Connection.
        Shared\Utils::onCommunicationException(new MalformedServerResponse(
            $this->_redisClient->getConnection(), $message
        ));
    }
}

class PubSubContext implements \Iterator {
    const SUBSCRIBE    = 'subscribe';
    const UNSUBSCRIBE  = 'unsubscribe';
    const PSUBSCRIBE   = 'psubscribe';
    const PUNSUBSCRIBE = 'punsubscribe';
    const MESSAGE      = 'message';
    const PMESSAGE     = 'pmessage';

    const STATUS_VALID       = 0x0001;
    const STATUS_SUBSCRIBED  = 0x0010;
    const STATUS_PSUBSCRIBED = 0x0100;

    private $_redisClient, $_position, $_options;

    public function __construct(Client $redisClient, Array $options = null) {
        $this->checkCapabilities($redisClient);
        $this->_options = $options ?: array();
        $this->_redisClient = $redisClient;
        $this->_statusFlags = self::STATUS_VALID;

        $this->genericSubscribeInit('subscribe');
        $this->genericSubscribeInit('psubscribe');
    }

    public function __destruct() {
        $this->closeContext();
    }

    private function checkCapabilities(Client $redisClient) {
        if (Shared\Utils::isCluster($redisClient->getConnection())) {
            throw new \Predis\ClientException(
                'Cannot initialize a PUB/SUB context over a cluster of connections'
            );
        }
        $profile = $redisClient->getProfile();
        $commands = array('publish', 'subscribe', 'unsubscribe', 'psubscribe', 'punsubscribe');
        if ($profile->supportsCommands($commands) === false) {
            throw new \Predis\ClientException(
                'The current profile does not support PUB/SUB related commands'
            );
        }
    }

    private function genericSubscribeInit($subscribeAction) {
        if (isset($this->_options[$subscribeAction])) {
            if (is_array($this->_options[$subscribeAction])) {
                foreach ($this->_options[$subscribeAction] as $subscription) {
                    $this->$subscribeAction($subscription);
                }
            }
            else {
                $this->$subscribeAction($this->_options[$subscribeAction]);
            }
        }
    }

    private function isFlagSet($value) {
        return ($this->_statusFlags & $value) === $value;
    }

    public function subscribe(/* arguments */) {
        $this->writeCommand(self::SUBSCRIBE, func_get_args());
        $this->_statusFlags |= self::STATUS_SUBSCRIBED;
    }

    public function unsubscribe(/* arguments */) {
        $this->writeCommand(self::UNSUBSCRIBE, func_get_args());
    }

    public function psubscribe(/* arguments */) {
        $this->writeCommand(self::PSUBSCRIBE, func_get_args());
        $this->_statusFlags |= self::STATUS_PSUBSCRIBED;
    }

    public function punsubscribe(/* arguments */) {
        $this->writeCommand(self::PUNSUBSCRIBE, func_get_args());
    }

    public function closeContext() {
        if ($this->valid()) {
            if ($this->isFlagSet(self::STATUS_SUBSCRIBED)) {
                $this->unsubscribe();
            }
            if ($this->isFlagSet(self::STATUS_PSUBSCRIBED)) {
                $this->punsubscribe();
            }
        }
    }

    private function writeCommand($method, $arguments) {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            $arguments = $arguments[0];
        }
        $command = $this->_redisClient->createCommand($method, $arguments);
        $this->_redisClient->getConnection()->writeCommand($command);
    }

    public function rewind() {
        // NOOP
    }

    public function current() {
        return $this->getValue();
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        if ($this->isFlagSet(self::STATUS_VALID)) {
            $this->_position++;
        }
        return $this->_position;
    }

    public function valid() {
        $subscriptions = self::STATUS_SUBSCRIBED + self::STATUS_PSUBSCRIBED;
        return $this->isFlagSet(self::STATUS_VALID) 
            && ($this->_statusFlags & $subscriptions) > 0;
    }

    private function invalidate() {
        $this->_statusFlags = 0x0000;
    }

    private function getValue() {
        $reader     = $this->_redisClient->getResponseReader();
        $connection = $this->_redisClient->getConnection();
        $response   = $reader->read($connection);

        switch ($response[0]) {
            case self::SUBSCRIBE:
            case self::UNSUBSCRIBE:
            case self::PSUBSCRIBE:
            case self::PUNSUBSCRIBE:
                if ($response[2] === 0) {
                    $this->invalidate();
                }
            case self::MESSAGE:
                return (object) array(
                    'kind'    => $response[0],
                    'channel' => $response[1],
                    'payload' => $response[2],
                );
            case self::PMESSAGE:
                return (object) array(
                    'kind'    => $response[0],
                    'pattern' => $response[1],
                    'channel' => $response[2],
                    'payload' => $response[3],
                );
            default:
                throw new \Predis\ClientException(
                    "Received an unknown message type {$response[0]} inside of a pubsub context"
                );
        }
    }
}

/* ------------------------------------------------------------------------- */

class ConnectionParameters {
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 6379;
    const DEFAULT_TIMEOUT = 5;
    private $_parameters;

    public function __construct($parameters = null) {
        $parameters = $parameters ?: array();
        $this->_parameters = is_array($parameters) 
            ? self::filterConnectionParams($parameters) 
            : self::parseURI($parameters);
    }

    private static function parseURI($uri) {
        $parsed = @parse_url($uri);

        if ($parsed == false || $parsed['scheme'] != 'redis' || $parsed['host'] == null) {
            throw new ClientException("Invalid URI: $uri");
        }

        if (array_key_exists('query', $parsed)) {
            $details = array();
            foreach (explode('&', $parsed['query']) as $kv) {
                list($k, $v) = explode('=', $kv);
                switch ($k) {
                    case 'database':
                        $details['database'] = $v;
                        break;
                    case 'password':
                        $details['password'] = $v;
                        break;
                    case 'connection_async':
                        $details['connection_async'] = $v;
                        break;
                    case 'connection_persistent':
                        $details['connection_persistent'] = $v;
                        break;
                    case 'connection_timeout':
                        $details['connection_timeout'] = $v;
                        break;
                    case 'read_write_timeout':
                        $details['read_write_timeout'] = $v;
                        break;
                    case 'alias':
                        $details['alias'] = $v;
                        break;
                    case 'weight':
                        $details['weight'] = $v;
                        break;
                }
            }
            $parsed = array_merge($parsed, $details);
        }

        return self::filterConnectionParams($parsed);
    }

    private static function getParamOrDefault(Array $parameters, $param, $default = null) {
        return array_key_exists($param, $parameters) ? $parameters[$param] : $default;
    }

    private static function filterConnectionParams($parameters) {
        return array(
            'host' => self::getParamOrDefault($parameters, 'host', self::DEFAULT_HOST), 
            'port' => (int) self::getParamOrDefault($parameters, 'port', self::DEFAULT_PORT), 
            'database' => self::getParamOrDefault($parameters, 'database'), 
            'password' => self::getParamOrDefault($parameters, 'password'), 
            'connection_async'   => self::getParamOrDefault($parameters, 'connection_async', false), 
            'connection_persistent' => self::getParamOrDefault($parameters, 'connection_persistent', false), 
            'connection_timeout' => self::getParamOrDefault($parameters, 'connection_timeout', self::DEFAULT_TIMEOUT), 
            'read_write_timeout' => self::getParamOrDefault($parameters, 'read_write_timeout'), 
            'alias'  => self::getParamOrDefault($parameters, 'alias'), 
            'weight' => self::getParamOrDefault($parameters, 'weight'), 
        );
    }

    public function __get($parameter) {
        return $this->_parameters[$parameter];
    }

    public function __isset($parameter) {
        return isset($this->_parameters[$parameter]);
    }
}

interface IConnection {
    public function connect();
    public function disconnect();
    public function isConnected();
    public function writeCommand(Command $command);
    public function readResponse(Command $command);
    public function executeCommand(Command $command);
}

class Connection implements IConnection {
    private $_params, $_socket, $_initCmds, $_reader;

    public function __construct(ConnectionParameters $parameters, ResponseReader $reader = null) {
        $this->_params   = $parameters;
        $this->_initCmds = array();
        $this->_reader   = $reader ?: new ResponseReader();
    }

    public function __destruct() {
        if (!$this->_params->connection_persistent) {
            $this->disconnect();
        }
    }

    public function isConnected() {
        return is_resource($this->_socket);
    }

    public function connect() {
        if ($this->isConnected()) {
            throw new ClientException('Connection already estabilished');
        }
        $uri = sprintf('tcp://%s:%d/', $this->_params->host, $this->_params->port);
        $connectFlags = STREAM_CLIENT_CONNECT;
        if ($this->_params->connection_async) {
            $connectFlags |= STREAM_CLIENT_ASYNC_CONNECT;
        }
        if ($this->_params->connection_persistent) {
            $connectFlags |= STREAM_CLIENT_PERSISTENT;
        }
        $this->_socket = @stream_socket_client(
            $uri, $errno, $errstr, $this->_params->connection_timeout, $connectFlags
        );

        if (!$this->_socket) {
            $this->onCommunicationException(trim($errstr), $errno);
        }

        if (isset($this->_params->read_write_timeout)) {
            $timeoutSeconds  = floor($this->_params->read_write_timeout);
            $timeoutUSeconds = ($this->_params->read_write_timeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($this->_socket, $timeoutSeconds, $timeoutUSeconds);
        }

        if (count($this->_initCmds) > 0){
            $this->sendInitializationCommands();
        }
    }

    public function disconnect() {
        if ($this->isConnected()) {
            fclose($this->_socket);
        }
    }

    public function pushInitCommand(Command $command){
        $this->_initCmds[] = $command;
    }

    private function sendInitializationCommands() {
        foreach ($this->_initCmds as $command) {
            $this->writeCommand($command);
        }
        foreach ($this->_initCmds as $command) {
            $this->readResponse($command);
        }
    }

    private function onCommunicationException($message, $code = null) {
        Shared\Utils::onCommunicationException(
            new CommunicationException($this, $message, $code)
        );
    }

    public function writeCommand(Command $command) {
        $this->writeBytes($command());
    }

    public function readResponse(Command $command) {
        $response = $this->_reader->read($this);
        return isset($response->skipParse) ? $response : $command->parseResponse($response);
    }

    public function executeCommand(Command $command) {
        $this->writeCommand($command);
        if ($command->closesConnection()) {
            return $this->disconnect();
        }
        return $this->readResponse($command);
    }

    public function rawCommand($rawCommandData, $closesConnection = false) {
        $this->writeBytes($rawCommandData);
        if ($closesConnection) {
            $this->disconnect();
            return;
        }
        return $this->_reader->read($this);
    }

    public function writeBytes($value) {
        $socket = $this->getSocket();
        while (($length = strlen($value)) > 0) {
            $written = fwrite($socket, $value);
            if ($length === $written) {
                return true;
            }
            if ($written === false || $written === 0) {
                $this->onCommunicationException('Error while writing bytes to the server');
            }
            $value = substr($value, $written);
        }
        return true;
    }

    public function readBytes($length) {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Length parameter must be greater than 0');
        }
        $socket = $this->getSocket();
        $value  = '';
        do {
            $chunk = fread($socket, $length);
            if ($chunk === false || $chunk === '') {
                $this->onCommunicationException('Error while reading bytes from the server');
            }
            $value .= $chunk;
        }
        while (($length -= strlen($chunk)) > 0);
        return $value;
    }

    public function readLine() {
        $socket = $this->getSocket();
        $value  = '';
        do {
            $chunk = fgets($socket);
            if ($chunk === false || $chunk === '') {
                $this->onCommunicationException('Error while reading line from the server');
            }
            $value .= $chunk;
        }
        while (substr($value, -2) !== "\r\n");
        return substr($value, 0, -2);
    }

    public function getSocket() {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->_socket;
    }

    public function getResponseReader() {
        return $this->_reader;
    }

    public function getParameters() {
        return $this->_params;
    }

    public function __toString() {
        return sprintf('%s:%d', $this->_params->host, $this->_params->port);
    }
}

class ConnectionCluster implements IConnection, \IteratorAggregate {
    private $_pool, $_distributor;

    public function __construct(Distribution\IDistributionStrategy $distributor = null) {
        $this->_pool = array();
        $this->_distributor = $distributor ?: new Distribution\HashRing();
    }

    public function isConnected() {
        foreach ($this->_pool as $connection) {
            if ($connection->isConnected()) {
                return true;
            }
        }
        return false;
    }

    public function connect() {
        foreach ($this->_pool as $connection) {
            $connection->connect();
        }
    }

    public function disconnect() {
        foreach ($this->_pool as $connection) {
            $connection->disconnect();
        }
    }

    public function add(Connection $connection) {
        $parameters = $connection->getParameters();
        if (isset($parameters->alias)) {
            $this->_pool[$parameters->alias] = $connection;
        }
        else {
            $this->_pool[] = $connection;
        }
        $this->_distributor->add($connection, $parameters->weight);
    }

    public function getConnection(Command $command) {
        if ($command->canBeHashed() === false) {
            throw new ClientException(
                sprintf("Cannot send '%s' commands to a cluster of connections", $command->getCommandId())
            );
        }
        return $this->_distributor->get($command->getHash($this->_distributor));
    }

    public function getConnectionById($id = null) {
        $alias = $id ?: 0;
        return isset($this->_pool[$alias]) ? $this->_pool[$alias] : null;
    }

    public function getIterator() {
        return new \ArrayIterator($this->_pool);
    }

    public function writeCommand(Command $command) {
        $this->getConnection($command)->writeCommand($command);
    }

    public function readResponse(Command $command) {
        return $this->getConnection($command)->readResponse($command);
    }

    public function executeCommand(Command $command) {
        $connection = $this->getConnection($command);
        $connection->writeCommand($command);
        return $connection->readResponse($command);
    }
}

/* ------------------------------------------------------------------------- */

abstract class RedisServerProfile {
    private static $_serverProfiles;
    private $_registeredCommands;

    public function __construct() {
        $this->_registeredCommands = $this->getSupportedCommands();
    }

    public abstract function getVersion();

    protected abstract function getSupportedCommands();

    public static function getDefault() {
        return self::get('default');
    }

    public static function getDevelopment() {
        return self::get('dev');
    }

    private static function predisServerProfiles() {
        return array(
            '1.2'     => '\Predis\RedisServer_v1_2',
            '2.0'     => '\Predis\RedisServer_v2_0',
            '2.2'     => '\Predis\RedisServer_v2_2',
            'default' => '\Predis\RedisServer_v2_0',
            'dev'     => '\Predis\RedisServer_vNext',
        );
    }

    public static function registerProfile($profileClass, $aliases) {
        if (!isset(self::$_serverProfiles)) {
            self::$_serverProfiles = self::predisServerProfiles();
        }

        $profileReflection = new \ReflectionClass($profileClass);

        if (!$profileReflection->isSubclassOf('\Predis\RedisServerProfile')) {
            throw new ClientException("Cannot register '$profileClass' as it is not a valid profile class");
        }

        if (is_array($aliases)) {
            foreach ($aliases as $alias) {
                self::$_serverProfiles[$alias] = $profileClass;
            }
        }
        else {
            self::$_serverProfiles[$aliases] = $profileClass;
        }
    }

    public static function get($version) {
        if (!isset(self::$_serverProfiles)) {
            self::$_serverProfiles = self::predisServerProfiles();
        }
        if (!isset(self::$_serverProfiles[$version])) {
            throw new ClientException("Unknown server profile: $version");
        }
        $profile = self::$_serverProfiles[$version];
        return new $profile();
    }

    public function supportsCommands(Array $commands) {
        foreach ($commands as $command) {
            if ($this->supportsCommand($command) === false) {
                return false;
            }
        }
        return true;
    }

    public function supportsCommand($command) {
        return isset($this->_registeredCommands[$command]);
    }

    public function createCommand($method, $arguments = array()) {
        if (!isset($this->_registeredCommands[$method])) {
            throw new ClientException("'$method' is not a registered Redis command");
        }
        $commandClass = $this->_registeredCommands[$method];
        $command = new $commandClass();
        $command->setArgumentsArray($arguments);
        return $command;
    }

    public function registerCommands(Array $commands) {
        foreach ($commands as $command => $aliases) {
            $this->registerCommand($command, $aliases);
        }
    }

    public function registerCommand($command, $aliases) {
        $commandReflection = new \ReflectionClass($command);

        if (!$commandReflection->isSubclassOf('\Predis\Command')) {
            throw new ClientException("Cannot register '$command' as it is not a valid Redis command");
        }

        if (is_array($aliases)) {
            foreach ($aliases as $alias) {
                $this->_registeredCommands[$alias] = $command;
            }
        }
        else {
            $this->_registeredCommands[$aliases] = $command;
        }
    }

    public function __toString() {
        return $this->getVersion();
    }
}

class RedisServer_v1_2 extends RedisServerProfile {
    public function getVersion() { return '1.2'; }
    public function getSupportedCommands() {
        return array(
            /* miscellaneous commands */
            'ping'      => '\Predis\Commands\Ping',
            'echo'      => '\Predis\Commands\DoEcho',
            'auth'      => '\Predis\Commands\Auth',

            /* connection handling */
            'quit'      => '\Predis\Commands\Quit',

            /* commands operating on string values */
            'set'                     => '\Predis\Commands\Set',
            'setnx'                   => '\Predis\Commands\SetPreserve',
                'setPreserve'         => '\Predis\Commands\SetPreserve',
            'mset'                    => '\Predis\Commands\SetMultiple',
                'setMultiple'         => '\Predis\Commands\SetMultiple',
            'msetnx'                  => '\Predis\Commands\SetMultiplePreserve',
                'setMultiplePreserve' => '\Predis\Commands\SetMultiplePreserve',
            'get'                     => '\Predis\Commands\Get',
            'mget'                    => '\Predis\Commands\GetMultiple',
                'getMultiple'         => '\Predis\Commands\GetMultiple',
            'getset'                  => '\Predis\Commands\GetSet',
                'getSet'              => '\Predis\Commands\GetSet',
            'incr'                    => '\Predis\Commands\Increment',
                'increment'           => '\Predis\Commands\Increment',
            'incrby'                  => '\Predis\Commands\IncrementBy',
                'incrementBy'         => '\Predis\Commands\IncrementBy',
            'decr'                    => '\Predis\Commands\Decrement',
                'decrement'           => '\Predis\Commands\Decrement',
            'decrby'                  => '\Predis\Commands\DecrementBy',
                'decrementBy'         => '\Predis\Commands\DecrementBy',
            'exists'                  => '\Predis\Commands\Exists',
            'del'                     => '\Predis\Commands\Delete',
                'delete'              => '\Predis\Commands\Delete',
            'type'                    => '\Predis\Commands\Type',

            /* commands operating on the key space */
            'keys'               => '\Predis\Commands\Keys_v1_2',
            'randomkey'          => '\Predis\Commands\RandomKey',
                'randomKey'      => '\Predis\Commands\RandomKey',
            'rename'             => '\Predis\Commands\Rename',
            'renamenx'           => '\Predis\Commands\RenamePreserve',
                'renamePreserve' => '\Predis\Commands\RenamePreserve',
            'expire'             => '\Predis\Commands\Expire',
            'expireat'           => '\Predis\Commands\ExpireAt',
                'expireAt'       => '\Predis\Commands\ExpireAt',
            'dbsize'             => '\Predis\Commands\DatabaseSize',
                'databaseSize'   => '\Predis\Commands\DatabaseSize',
            'ttl'                => '\Predis\Commands\TimeToLive',
                'timeToLive'     => '\Predis\Commands\TimeToLive',

            /* commands operating on lists */
            'rpush'            => '\Predis\Commands\ListPushTail',
                'pushTail'     => '\Predis\Commands\ListPushTail',
            'lpush'            => '\Predis\Commands\ListPushHead',
                'pushHead'     => '\Predis\Commands\ListPushHead',
            'llen'             => '\Predis\Commands\ListLength',
                'listLength'   => '\Predis\Commands\ListLength',
            'lrange'           => '\Predis\Commands\ListRange',
                'listRange'    => '\Predis\Commands\ListRange',
            'ltrim'            => '\Predis\Commands\ListTrim',
                'listTrim'     => '\Predis\Commands\ListTrim',
            'lindex'           => '\Predis\Commands\ListIndex',
                'listIndex'    => '\Predis\Commands\ListIndex',
            'lset'             => '\Predis\Commands\ListSet',
                'listSet'      => '\Predis\Commands\ListSet',
            'lrem'             => '\Predis\Commands\ListRemove',
                'listRemove'   => '\Predis\Commands\ListRemove',
            'lpop'             => '\Predis\Commands\ListPopFirst',
                'popFirst'     => '\Predis\Commands\ListPopFirst',
            'rpop'             => '\Predis\Commands\ListPopLast',
                'popLast'      => '\Predis\Commands\ListPopLast',
            'rpoplpush'        => '\Predis\Commands\ListPopLastPushHead',
                'listPopLastPushHead'  => '\Predis\Commands\ListPopLastPushHead',

            /* commands operating on sets */
            'sadd'                      => '\Predis\Commands\SetAdd', 
                'setAdd'                => '\Predis\Commands\SetAdd',
            'srem'                      => '\Predis\Commands\SetRemove', 
                'setRemove'             => '\Predis\Commands\SetRemove',
            'spop'                      => '\Predis\Commands\SetPop',
                'setPop'                => '\Predis\Commands\SetPop',
            'smove'                     => '\Predis\Commands\SetMove', 
                'setMove'               => '\Predis\Commands\SetMove',
            'scard'                     => '\Predis\Commands\SetCardinality', 
                'setCardinality'        => '\Predis\Commands\SetCardinality',
            'sismember'                 => '\Predis\Commands\SetIsMember', 
                'setIsMember'           => '\Predis\Commands\SetIsMember',
            'sinter'                    => '\Predis\Commands\SetIntersection', 
                'setIntersection'       => '\Predis\Commands\SetIntersection',
            'sinterstore'               => '\Predis\Commands\SetIntersectionStore', 
                'setIntersectionStore'  => '\Predis\Commands\SetIntersectionStore',
            'sunion'                    => '\Predis\Commands\SetUnion', 
                'setUnion'              => '\Predis\Commands\SetUnion',
            'sunionstore'               => '\Predis\Commands\SetUnionStore', 
                'setUnionStore'         => '\Predis\Commands\SetUnionStore',
            'sdiff'                     => '\Predis\Commands\SetDifference', 
                'setDifference'         => '\Predis\Commands\SetDifference',
            'sdiffstore'                => '\Predis\Commands\SetDifferenceStore', 
                'setDifferenceStore'    => '\Predis\Commands\SetDifferenceStore',
            'smembers'                  => '\Predis\Commands\SetMembers', 
                'setMembers'            => '\Predis\Commands\SetMembers',
            'srandmember'               => '\Predis\Commands\SetRandomMember', 
                'setRandomMember'       => '\Predis\Commands\SetRandomMember',

            /* commands operating on sorted sets */
            'zadd'                          => '\Predis\Commands\ZSetAdd',
                'zsetAdd'                   => '\Predis\Commands\ZSetAdd',
            'zincrby'                       => '\Predis\Commands\ZSetIncrementBy',
                'zsetIncrementBy'           => '\Predis\Commands\ZSetIncrementBy',
            'zrem'                          => '\Predis\Commands\ZSetRemove',
                'zsetRemove'                => '\Predis\Commands\ZSetRemove',
            'zrange'                        => '\Predis\Commands\ZSetRange',
                'zsetRange'                 => '\Predis\Commands\ZSetRange',
            'zrevrange'                     => '\Predis\Commands\ZSetReverseRange',
                'zsetReverseRange'          => '\Predis\Commands\ZSetReverseRange',
            'zrangebyscore'                 => '\Predis\Commands\ZSetRangeByScore',
                'zsetRangeByScore'          => '\Predis\Commands\ZSetRangeByScore',
            'zcard'                         => '\Predis\Commands\ZSetCardinality',
                'zsetCardinality'           => '\Predis\Commands\ZSetCardinality',
            'zscore'                        => '\Predis\Commands\ZSetScore',
                'zsetScore'                 => '\Predis\Commands\ZSetScore',
            'zremrangebyscore'              => '\Predis\Commands\ZSetRemoveRangeByScore',
                'zsetRemoveRangeByScore'    => '\Predis\Commands\ZSetRemoveRangeByScore',

            /* multiple databases handling commands */
            'select'                => '\Predis\Commands\SelectDatabase', 
                'selectDatabase'    => '\Predis\Commands\SelectDatabase',
            'move'                  => '\Predis\Commands\MoveKey', 
                'moveKey'           => '\Predis\Commands\MoveKey',
            'flushdb'               => '\Predis\Commands\FlushDatabase', 
                'flushDatabase'     => '\Predis\Commands\FlushDatabase',
            'flushall'              => '\Predis\Commands\FlushAll', 
                'flushDatabases'    => '\Predis\Commands\FlushAll',

            /* sorting */
            'sort'                  => '\Predis\Commands\Sort',

            /* remote server control commands */
            'info'                  => '\Predis\Commands\Info',
            'slaveof'               => '\Predis\Commands\SlaveOf', 
                'slaveOf'           => '\Predis\Commands\SlaveOf',

            /* persistence control commands */
            'save'                  => '\Predis\Commands\Save',
            'bgsave'                => '\Predis\Commands\BackgroundSave', 
                'backgroundSave'    => '\Predis\Commands\BackgroundSave',
            'lastsave'              => '\Predis\Commands\LastSave', 
                'lastSave'          => '\Predis\Commands\LastSave',
            'shutdown'              => '\Predis\Commands\Shutdown',
            'bgrewriteaof'                      =>  '\Predis\Commands\BackgroundRewriteAppendOnlyFile',
            'backgroundRewriteAppendOnlyFile'   =>  '\Predis\Commands\BackgroundRewriteAppendOnlyFile',
        );
    }
}

class RedisServer_v2_0 extends RedisServer_v1_2 {
    public function getVersion() { return '2.0'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* transactions */
            'multi'                     => '\Predis\Commands\Multi',
            'exec'                      => '\Predis\Commands\Exec',
            'discard'                   => '\Predis\Commands\Discard',

            /* commands operating on string values */
            'setex'                     => '\Predis\Commands\SetExpire',
                'setExpire'             => '\Predis\Commands\SetExpire',
            'append'                    => '\Predis\Commands\Append',
            'substr'                    => '\Predis\Commands\Substr',

            /* commands operating on the key space */
            'keys'                      => '\Predis\Commands\Keys',

            /* commands operating on lists */
            'blpop'                     => '\Predis\Commands\ListPopFirstBlocking',
                'popFirstBlocking'      => '\Predis\Commands\ListPopFirstBlocking',
            'brpop'                     => '\Predis\Commands\ListPopLastBlocking',
                'popLastBlocking'       => '\Predis\Commands\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'zunionstore'               => '\Predis\Commands\ZSetUnionStore',
                'zsetUnionStore'        => '\Predis\Commands\ZSetUnionStore',
            'zinterstore'               => '\Predis\Commands\ZSetIntersectionStore',
                'zsetIntersectionStore' => '\Predis\Commands\ZSetIntersectionStore',
            'zcount'                    => '\Predis\Commands\ZSetCount',
                'zsetCount'             => '\Predis\Commands\ZSetCount',
            'zrank'                     => '\Predis\Commands\ZSetRank',
                'zsetRank'              => '\Predis\Commands\ZSetRank',
            'zrevrank'                  => '\Predis\Commands\ZSetReverseRank',
                'zsetReverseRank'       => '\Predis\Commands\ZSetReverseRank',
            'zremrangebyrank'           => '\Predis\Commands\ZSetRemoveRangeByRank',
                'zsetRemoveRangeByRank' => '\Predis\Commands\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'hset'                      => '\Predis\Commands\HashSet',
                'hashSet'               => '\Predis\Commands\HashSet',
            'hsetnx'                    => '\Predis\Commands\HashSetPreserve',
                'hashSetPreserve'       => '\Predis\Commands\HashSetPreserve',
            'hmset'                     => '\Predis\Commands\HashSetMultiple',
                'hashSetMultiple'       => '\Predis\Commands\HashSetMultiple',
            'hincrby'                   => '\Predis\Commands\HashIncrementBy',
                'hashIncrementBy'       => '\Predis\Commands\HashIncrementBy',
            'hget'                      => '\Predis\Commands\HashGet',
                'hashGet'               => '\Predis\Commands\HashGet',
            'hmget'                     => '\Predis\Commands\HashGetMultiple',
                'hashGetMultiple'       => '\Predis\Commands\HashGetMultiple',
            'hdel'                      => '\Predis\Commands\HashDelete',
                'hashDelete'            => '\Predis\Commands\HashDelete',
            'hexists'                   => '\Predis\Commands\HashExists',
                'hashExists'            => '\Predis\Commands\HashExists',
            'hlen'                      => '\Predis\Commands\HashLength',
                'hashLength'            => '\Predis\Commands\HashLength',
            'hkeys'                     => '\Predis\Commands\HashKeys',
                'hashKeys'              => '\Predis\Commands\HashKeys',
            'hvals'                     => '\Predis\Commands\HashValues',
                'hashValues'            => '\Predis\Commands\HashValues',
            'hgetall'                   => '\Predis\Commands\HashGetAll',
                'hashGetAll'            => '\Predis\Commands\HashGetAll',

            /* publish - subscribe */
            'subscribe'                 => '\Predis\Commands\Subscribe',
            'unsubscribe'               => '\Predis\Commands\Unsubscribe',
            'psubscribe'                => '\Predis\Commands\SubscribeByPattern',
            'punsubscribe'              => '\Predis\Commands\UnsubscribeByPattern',
            'publish'                   => '\Predis\Commands\Publish',

            /* remote server control commands */
            'config'                    => '\Predis\Commands\Config',
                'configuration'         => '\Predis\Commands\Config',
        ));
    }
}

class RedisServer_v2_2 extends RedisServer_v2_0 {
    public function getVersion() { return '2.2'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* transactions */
            'watch'                     => '\Predis\Commands\Watch',
            'unwatch'                   => '\Predis\Commands\Unwatch',

            /* commands operating on string values */
            'strlen'                    => '\Predis\Commands\Strlen',
            'setrange'                  => '\Predis\Commands\SetRange',
            'getrange'                  => '\Predis\Commands\Substr',
            'setbit'                    => '\Predis\Commands\SetBit',
            'getbit'                    => '\Predis\Commands\GetBit',

            /* commands operating on the key space */
            'persist'                   => '\Predis\Commands\Persist',

            /* commands operating on lists */
            'rpushx'                    => '\Predis\Commands\ListPushTailX',
            'lpushx'                    => '\Predis\Commands\ListPushHeadX',
            'linsert'                   => '\Predis\Commands\ListInsert',
            'brpoplpush'                => '\Predis\Commands\ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'zrevrangebyscore'          => '\Predis\Commands\ZSetReverseRangeByScore',
        ));
    }
}

class RedisServer_vNext extends RedisServer_v2_2 {
    public function getVersion() { return 'DEV'; }
}

/* ------------------------------------------------------------------------- */

namespace Predis\Pipeline;

interface IPipelineExecutor {
    public function execute(\Predis\IConnection $connection, &$commands);
}

class StandardExecutor implements IPipelineExecutor {
    public function execute(\Predis\IConnection $connection, &$commands) {
        $sizeofPipe = count($commands);
        $values = array();

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }
        try {
            for ($i = 0; $i < $sizeofPipe; $i++) {
                $response = $connection->readResponse($commands[$i]);
                $values[] = $response instanceof \Iterator
                    ? iterator_to_array($response)
                    : $response;
                unset($commands[$i]);
            }
        }
        catch (\Predis\ServerException $exception) {
            // force disconnection to prevent protocol desynchronization
            $connection->disconnect();
            throw $exception;
        }

        return $values;
    }
}

class SafeExecutor implements IPipelineExecutor {
    public function execute(\Predis\IConnection $connection, &$commands) {
        $sizeofPipe = count($commands);
        $values = array();

        foreach ($commands as $command) {
            try {
                $connection->writeCommand($command);
            }
            catch (\Predis\CommunicationException $exception) {
                return array_fill(0, $sizeofPipe, $exception);
            }
        }

        for ($i = 0; $i < $sizeofPipe; $i++) {
            $command = $commands[$i];
            unset($commands[$i]);
            try {
                $response = $connection->readResponse($command);
                $values[] = ($response instanceof \Iterator
                    ? iterator_to_array($response)
                    : $response
                );
            }
            catch (\Predis\ServerException $exception) {
                $values[] = $exception->toResponseError();
            }
            catch (\Predis\CommunicationException $exception) {
                $toAdd  = count($commands) - count($values);
                $values = array_merge($values, array_fill(0, $toAdd, $exception));
                break;
            }
        }

        return $values;
    }
}

class SafeClusterExecutor implements IPipelineExecutor {
    public function execute(\Predis\IConnection $connection, &$commands) {
        $connectionExceptions = array();
        $sizeofPipe = count($commands);
        $values = array();

        foreach ($commands as $command) {
            $cmdConnection = $connection->getConnection($command);
            if (isset($connectionExceptions[spl_object_hash($cmdConnection)])) {
                continue;
            }
            try {
                $cmdConnection->writeCommand($command);
            }
            catch (\Predis\CommunicationException $exception) {
                $connectionExceptions[spl_object_hash($cmdConnection)] = $exception;
            }
        }

        for ($i = 0; $i < $sizeofPipe; $i++) {
            $command = $commands[$i];
            unset($commands[$i]);

            $cmdConnection = $connection->getConnection($command);
            $connectionObjectHash = spl_object_hash($cmdConnection);

            if (isset($connectionExceptions[$connectionObjectHash])) {
                $values[] = $connectionExceptions[$connectionObjectHash];
                continue;
            }

            try {
                $response = $cmdConnection->readResponse($command);
                $values[] = ($response instanceof \Iterator
                    ? iterator_to_array($response)
                    : $response
                );
            }
            catch (\Predis\ServerException $exception) {
                $values[] = $exception->toResponseError();
            }
            catch (\Predis\CommunicationException $exception) {
                $values[] = $exception;
                $connectionExceptions[$connectionObjectHash] = $exception;
            }
        }

        return $values;
    }
}

/* ------------------------------------------------------------------------- */

namespace Predis\Distribution;

interface IDistributionStrategy {
    public function add($node, $weight = null);
    public function remove($node);
    public function get($key);
    public function generateKey($value);
}

class EmptyRingException extends \Exception { }

class HashRing implements IDistributionStrategy {
    const DEFAULT_REPLICAS = 128;
    const DEFAULT_WEIGHT   = 100;
    private $_nodes, $_ring, $_ringKeys, $_ringKeysCount, $_replicas;

    public function __construct($replicas = self::DEFAULT_REPLICAS) {
        $this->_replicas = $replicas;
        $this->_nodes    = array();
    }

    public function add($node, $weight = null) {
        // NOTE: in case of collisions in the hashes of the nodes, the node added
        //       last wins, thus the order in which nodes are added is significant.
        $this->_nodes[] = array('object' => $node, 'weight' => (int) $weight ?: $this::DEFAULT_WEIGHT);
        $this->reset();
    }

    public function remove($node) {
        // NOTE: a node is removed by resetting the ring so that it's recreated from 
        //       scratch, in order to reassign possible hashes with collisions to the 
        //       right node according to the order in which they were added in the 
        //       first place.
        for ($i = 0; $i < count($this->_nodes); ++$i) {
            if ($this->_nodes[$i]['object'] === $node) {
                array_splice($this->_nodes, $i, 1);
                $this->reset();
                break;
            }
        }
    }

    private function reset() {
        unset($this->_ring);
        unset($this->_ringKeys);
        unset($this->_ringKeysCount);
    }

    private function isInitialized() {
        return isset($this->_ringKeys);
    }

    private function computeTotalWeight() {
        // TODO: array_reduce + lambda for PHP 5.3
        $totalWeight = 0;
        foreach ($this->_nodes as $node) {
            $totalWeight += $node['weight'];
        }
        return $totalWeight;
    }

    private function initialize() {
        if ($this->isInitialized()) {
            return;
        }
        if (count($this->_nodes) === 0) {
            throw new EmptyRingException('Cannot initialize empty hashring');
        }

        $this->_ring = array();
        $totalWeight = $this->computeTotalWeight();
        $nodesCount  = count($this->_nodes);
        foreach ($this->_nodes as $node) {
            $weightRatio = $node['weight'] / $totalWeight;
            $this->addNodeToRing($this->_ring, $node, $nodesCount, $this->_replicas, $weightRatio);
        }
        ksort($this->_ring, SORT_NUMERIC);
        $this->_ringKeys = array_keys($this->_ring);
        $this->_ringKeysCount = count($this->_ringKeys);
    }

    protected function addNodeToRing(&$ring, $node, $totalNodes, $replicas, $weightRatio) {
        $nodeObject = $node['object'];
        $nodeHash = (string) $nodeObject;
        $replicas = (int) round($weightRatio * $totalNodes * $replicas);
        for ($i = 0; $i < $replicas; $i++) {
            $key = crc32("$nodeHash:$i");
            $ring[$key] = $nodeObject;
        }
    }

    public function generateKey($value) {
        return crc32($value);
    }

    public function get($key) {
        return $this->_ring[$this->getNodeKey($key)];
    }

    private function getNodeKey($key) {
        $this->initialize();
        $ringKeys = $this->_ringKeys;
        $upper = $this->_ringKeysCount - 1;
        $lower = 0;

        while ($lower <= $upper) {
            $index = ($lower + $upper) >> 1;
            $item  = $ringKeys[$index];
            if ($item > $key) {
                $upper = $index - 1;
            }
            else if ($item < $key) {
                $lower = $index + 1;
            }
            else {
                return $item;
            }
        }
        return $ringKeys[$this->wrapAroundStrategy($upper, $lower, $this->_ringKeysCount)];
    }

    protected function wrapAroundStrategy($upper, $lower, $ringKeysCount) {
        // NOTE: binary search for the last item in _ringkeys with a value 
        //       less or equal to the key. If no such item exists, return the 
        //       last item.
        return $upper >= 0 ? $upper : $ringKeysCount - 1;
    }
}

class KetamaPureRing extends HashRing {
    const DEFAULT_REPLICAS = 160;

    public function __construct() {
        parent::__construct($this::DEFAULT_REPLICAS);
    }

    protected function addNodeToRing(&$ring, $node, $totalNodes, $replicas, $weightRatio) {
        $nodeObject = $node['object'];
        $nodeHash = (string) $nodeObject;
        $replicas = (int) floor($weightRatio * $totalNodes * ($replicas / 4));
        for ($i = 0; $i < $replicas; $i++) {
            $unpackedDigest = unpack('V4', md5("$nodeHash-$i", true));
            foreach ($unpackedDigest as $key) {
                $ring[$key] = $nodeObject;
            }
        }
    }

    public function generateKey($value) {
        $hash = unpack('V', md5($value, true));
        return $hash[1];
    }

    protected function wrapAroundStrategy($upper, $lower, $ringKeysCount) {
        // NOTE: binary search for the first item in _ringkeys with a value 
        //       greater or equal to the key. If no such item exists, return the 
        //       first item.
        return $lower < $ringKeysCount ? $lower : 0;
    }
}

/* ------------------------------------------------------------------------- */

namespace Predis\Shared;

class Utils {
    public static function isCluster(\Predis\IConnection $connection) {
        return $connection instanceof \Predis\ConnectionCluster;
    }

    public static function onCommunicationException(\Predis\CommunicationException $exception) {
        if ($exception->shouldResetConnection()) {
            $connection = $exception->getConnection();
            if ($connection->isConnected()) {
                $connection->disconnect();
            }
        }
        throw $exception;
    }
}

abstract class MultiBulkResponseIteratorBase implements \Iterator, \Countable {
    protected $_position, $_current, $_replySize;

    public function rewind() {
        // NOOP
    }

    public function current() {
        return $this->_current;
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        if (++$this->_position < $this->_replySize) {
            $this->_current = $this->getValue();
        }
        return $this->_position;
    }

    public function valid() {
        return $this->_position < $this->_replySize;
    }

    public function count() {
        // NOTE: use count if you want to get the size of the current multi-bulk 
        //       response without using iterator_count (which actually consumes 
        //       our iterator to calculate the size, and we cannot perform a rewind)
        return $this->_replySize;
    }

    protected abstract function getValue();
}

class MultiBulkResponseIterator extends MultiBulkResponseIteratorBase {
    private $_connection;

    public function __construct(\Predis\Connection $connection, $size) {
        $this->_connection = $connection;
        $this->_reader     = $connection->getResponseReader();
        $this->_position   = 0;
        $this->_current    = $size > 0 ? $this->getValue() : null;
        $this->_replySize  = $size;
    }

    public function __destruct() {
        // when the iterator is garbage-collected (e.g. it goes out of the
        // scope of a foreach) but it has not reached its end, we must sync
        // the client with the queued elements that have not been read from
        // the connection with the server.
        $this->sync();
    }

    public function sync($drop = false) {
        if ($drop == true) {
            if ($this->valid()) {
                $this->_position = $this->_replySize;
                $this->_connection->disconnect();
            }
        }
        else {
            while ($this->valid()) {
                $this->next();
            }
        }
    }

    protected function getValue() {
        return $this->_reader->read($this->_connection);
    }
}

class MultiBulkResponseKVIterator extends MultiBulkResponseIteratorBase {
    private $_iterator;

    public function __construct(MultiBulkResponseIterator $iterator) {
        $virtualSize = count($iterator) / 2;

        $this->_iterator   = $iterator;
        $this->_position   = 0;
        $this->_current    = $virtualSize > 0 ? $this->getValue() : null;
        $this->_replySize  = $virtualSize;
    }

    public function __destruct() {
        $this->_iterator->sync();
    }

    protected function getValue() {
        $k = $this->_iterator->current();
        $this->_iterator->next();
        $v = $this->_iterator->current();
        $this->_iterator->next();
        return array($k, $v);
    }
}

/* ------------------------------------------------------------------------- */

namespace Predis\Commands;

/* miscellaneous commands */
class Ping extends  \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}

class DoEcho extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}

class Auth extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}

/* connection handling */
class Quit extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}

/* commands operating on string values */
class Set extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SET'; }
}

class SetExpire extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SETEX'; }
}

class SetPreserve extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetMultiple extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSET'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            $flattenedKVs = array();
            $args = &$arguments[0];
            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }
}

class SetMultiplePreserve extends \Predis\Commands\SetMultiple {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Get extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'GET'; }
}

class GetMultiple extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
}

class GetSet extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'GETSET'; }
}

class Increment extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'INCR'; }
}

class IncrementBy extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'INCRBY'; }
}

class Decrement extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'DECR'; }
}

class DecrementBy extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'DECRBY'; }
}

class Exists extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Delete extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'DEL'; }
}

class Type extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'TYPE'; }
}

class Append extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'APPEND'; }
}

class SetRange extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SETRANGE'; }
}

class Substr extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SUBSTR'; }
}

class SetBit extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SETBIT'; }
}

class GetBit extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'GETBIT'; }
}

class Strlen extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'STRLEN'; }
}

/* commands operating on the key space */
class Keys extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
}

class Keys_v1_2 extends Keys {
    public function parseResponse($data) {
        return explode(' ', $data);
    }
}

class RandomKey extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}

class Rename extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}

class RenamePreserve extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Expire extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ExpireAt extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Persist extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'PERSIST'; }
    public function parseResponse($data) { return (bool) $data; }
}

class DatabaseSize extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}

class TimeToLive extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'TTL'; }
}

/* commands operating on lists */
class ListPushTail extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'RPUSH'; }
}

class ListPushTailX extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'RPUSHX'; }
}

class ListPushHead extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LPUSH'; }
}

class ListPushHeadX extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LPUSHX'; }
}

class ListLength extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LLEN'; }
}

class ListRange extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LRANGE'; }
}

class ListTrim extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LTRIM'; }
}

class ListIndex extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LINDEX'; }
}

class ListSet extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LSET'; }
}

class ListRemove extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LREM'; }
}

class ListPopLastPushHead extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'RPOPLPUSH'; }
}

class ListPopLastPushHeadBlocking extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'BRPOPLPUSH'; }
}

class ListPopFirst extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LPOP'; }
}

class ListPopLast extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'RPOP'; }
}

class ListPopFirstBlocking extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'BLPOP'; }
}

class ListPopLastBlocking extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'BRPOP'; }
}

class ListInsert extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'LINSERT'; }
}

/* commands operating on sets */
class SetAdd extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetRemove extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetPop  extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SPOP'; }
}

class SetMove extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetCardinality extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SCARD'; }
}

class SetIsMember extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetIntersection extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SINTER'; }
}

class SetIntersectionStore extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SINTERSTORE'; }
}

class SetUnion extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SUNION'; }
}

class SetUnionStore extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SUNIONSTORE'; }
}

class SetDifference extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SDIFF'; }
}

class SetDifferenceStore extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SDIFFSTORE'; }
}

class SetMembers extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SMEMBERS'; }
}

class SetRandomMember extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SRANDMEMBER'; }
}

/* commands operating on sorted sets */
class ZSetAdd extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ZSetIncrementBy extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZINCRBY'; }
}

class ZSetRemove extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ZSetUnionStore extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZUNIONSTORE'; }
    public function filterArguments(Array $arguments) {
        $options = array();
        $argc    = count($arguments);
        if ($argc > 1 && is_array($arguments[$argc - 1])) {
            $options = $this->prepareOptions(array_pop($arguments));
        }
        $args = is_array($arguments[0]) ? $arguments[0] : $arguments;
        return  array_merge($args, $options);
    }
    private function prepareOptions($options) {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();
        if (isset($opts['WEIGHTS']) && is_array($opts['WEIGHTS'])) {
            $finalizedOpts[] = 'WEIGHTS';
            foreach ($opts['WEIGHTS'] as $weight) {
                $finalizedOpts[] = $weight;
            }
        }
        if (isset($opts['AGGREGATE'])) {
            $finalizedOpts[] = 'AGGREGATE';
            $finalizedOpts[] = $opts['AGGREGATE'];
        }
        return $finalizedOpts;
    }
}

class ZSetIntersectionStore extends \Predis\Commands\ZSetUnionStore {
    public function getCommandId() { return 'ZINTERSTORE'; }
}

class ZSetRange extends \Predis\MultiBulkCommand {
    private $_withScores = false;
    public function getCommandId() { return 'ZRANGE'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 4) {
            $lastType = gettype($arguments[3]);
            if ($lastType === 'string' && strtolower($arguments[3]) === 'withscores') {
                // used for compatibility with older versions
                $arguments[3] = array('WITHSCORES' => true);
                $lastType = 'array';
            }
            if ($lastType === 'array') {
                $options = $this->prepareOptions(array_pop($arguments));
                return array_merge($arguments, $options);
            }
        }
        return $arguments;
    }
    protected function prepareOptions($options) {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();
        if (isset($opts['WITHSCORES'])) {
            $finalizedOpts[] = 'WITHSCORES';
            $this->_withScores = true;
        }
        return $finalizedOpts;
    }
    public function parseResponse($data) {
        if ($this->_withScores) {
            if ($data instanceof \Iterator) {
                return new \Predis\Shared\MultiBulkResponseKVIterator($data);
            }
            $result = array();
            for ($i = 0; $i < count($data); $i++) {
                $result[] = array($data[$i], $data[++$i]);
            }
            return $result;
        }
        return $data;
    }
}

class ZSetReverseRange extends \Predis\Commands\ZSetRange {
    public function getCommandId() { return 'ZREVRANGE'; }
}

class ZSetRangeByScore extends \Predis\Commands\ZSetRange {
    public function getCommandId() { return 'ZRANGEBYSCORE'; }
    protected function prepareOptions($options) {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();
        if (isset($opts['LIMIT']) && is_array($opts['LIMIT'])) {
            $limit = array_change_key_case($opts['LIMIT'], CASE_UPPER);
            $finalizedOpts[] = 'LIMIT';
            $finalizedOpts[] = isset($limit['OFFSET']) ? $limit['OFFSET'] : $limit[0];
            $finalizedOpts[] = isset($limit['COUNT']) ? $limit['COUNT'] : $limit[1];
        }
        return array_merge($finalizedOpts, parent::prepareOptions($options));
    }
}

class ZSetReverseRangeByScore extends \Predis\Commands\ZSetRangeByScore {
    public function getCommandId() { return 'ZREVRANGEBYSCORE'; }
}

class ZSetCount extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZCOUNT'; }
}

class ZSetCardinality extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZCARD'; }
}

class ZSetScore extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZSCORE'; }
}

class ZSetRemoveRangeByScore extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZREMRANGEBYSCORE'; }
}

class ZSetRank extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZRANK'; }
}

class ZSetReverseRank extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZREVRANK'; }
}

class ZSetRemoveRangeByRank extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'ZREMRANGEBYRANK'; }
}

/* commands operating on hashes */
class HashSet extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HSET'; }
    public function parseResponse($data) { return (bool) $data; }
}

class HashSetPreserve extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class HashSetMultiple extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HMSET'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $flattenedKVs = array($arguments[0]);
            $args = &$arguments[1];
            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }
}

class HashIncrementBy extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HINCRBY'; }
}

class HashGet extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HGET'; }
}

class HashGetMultiple extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HMGET'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $flattenedKVs = array($arguments[0]);
            $args = &$arguments[1];
            foreach ($args as $v) {
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }
}

class HashDelete extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HDEL'; }
    public function parseResponse($data) { return (bool) $data; }
}

class HashExists extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HEXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class HashLength extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HLEN'; }
}

class HashKeys extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HKEYS'; }
}

class HashValues extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HVALS'; }
}

class HashGetAll extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'HGETALL'; }
    public function parseResponse($data) {
        if ($data instanceof \Iterator) {
            return new \Predis\Shared\MultiBulkResponseKVIterator($data);
        }
        $result = array();
        for ($i = 0; $i < count($data); $i++) {
            $result[$data[$i]] = $data[++$i];
        }
        return $result;
    }
}

/* multiple databases handling commands */
class SelectDatabase extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SELECT'; }
}

class MoveKey extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class FlushDatabase extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}

class FlushAll extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}

/* sorting */
class Sort extends \Predis\MultiBulkCommand {
    public function getCommandId() { return 'SORT'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 1) {
            return $arguments;
        }

        $query = array($arguments[0]);
        $sortParams = array_change_key_case($arguments[1], CASE_UPPER);

        if (isset($sortParams['BY'])) {
            $query[] = 'BY';
            $query[] = $sortParams['BY'];
        }
        if (isset($sortParams['GET'])) {
            $getargs = $sortParams['GET'];
            if (is_array($getargs)) {
                foreach ($getargs as $getarg) {
                    $query[] = 'GET';
                    $query[] = $getarg;
                }
            }
            else {
                $query[] = 'GET';
                $query[] = $getargs;
            }
        }
        if (isset($sortParams['LIMIT']) && is_array($sortParams['LIMIT']) 
            && count($sortParams['LIMIT']) == 2) {

            $query[] = 'LIMIT';
            $query[] = $sortParams['LIMIT'][0];
            $query[] = $sortParams['LIMIT'][1];
        }
        if (isset($sortParams['SORT'])) {
            $query[] = strtoupper($sortParams['SORT']);
        }
        if (isset($sortParams['ALPHA']) && $sortParams['ALPHA'] == true) {
            $query[] = 'ALPHA';
        }
        if (isset($sortParams['STORE'])) {
            $query[] = 'STORE';
            $query[] = $sortParams['STORE'];
        }

        return $query;
    }
}

/* transactions */
class Multi extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MULTI'; }
}

class Exec extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'EXEC'; }
}

class Discard extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DISCARD'; }
}

class Watch extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'WATCH'; }
    public function filterArguments(Array $arguments) {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }
        return $arguments;
    }
    public function parseResponse($data) { return (bool) $data; }
}

class Unwatch extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNWATCH'; }
    public function parseResponse($data) { return (bool) $data; }
}

/* publish/subscribe */
class Subscribe extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SUBSCRIBE'; }
}

class Unsubscribe extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNSUBSCRIBE'; }
}

class SubscribeByPattern extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PSUBSCRIBE'; }
}

class UnsubscribeByPattern extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PUNSUBSCRIBE'; }
}

class Publish extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PUBLISH'; }
}

/* persistence control commands */
class Save extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}

class BackgroundSave extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGSAVE'; }
    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}

class BackgroundRewriteAppendOnlyFile extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGREWRITEAOF'; }
    public function parseResponse($data) {
        return $data == 'Background append only file rewriting started';
    }
}

class LastSave extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}

class Shutdown extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}

/* remote server control commands */
class Info extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'INFO'; }
    public function parseResponse($data) {
        $info      = array();
        $infoLines = explode("\r\n", $data, -1);
        foreach ($infoLines as $row) {
            list($k, $v) = explode(':', $row);
            if (!preg_match('/^db\d+$/', $k)) {
                $info[$k] = $v;
            }
            else {
                $db = array();
                foreach (explode(',', $v) as $dbvar) {
                    list($dbvk, $dbvv) = explode('=', $dbvar);
                    $db[trim($dbvk)] = $dbvv;
                }
                $info[$k] = $db;
            }
        }
        return $info;
    }
}

class SlaveOf extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SLAVEOF'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }
        return $arguments;
    }
}

class Config extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'CONFIG'; }
}
?>
