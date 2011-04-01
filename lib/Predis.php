<?php
class PredisException extends Exception { }

// Client-side errors
class Predis_ClientException extends PredisException { }

// Aborted multi/exec
class Predis_AbortedMultiExec extends PredisException { }

// Server-side errors
class Predis_ServerException extends PredisException {
    public function toResponseError() {
        return new Predis_ResponseError($this->getMessage());
    }
}

// Communication errors
class Predis_CommunicationException extends PredisException {
    private $_connection;

    public function __construct(Predis_Connection $connection, $message = null, $code = null) {
        $this->_connection = $connection;
        parent::__construct($message, $code);
    }

    public function getConnection() { return $this->_connection; }
    public function shouldResetConnection() {  return true; }
}

// Unexpected responses
class Predis_MalformedServerResponse extends Predis_CommunicationException { }

/* ------------------------------------------------------------------------- */

class Predis_Client {
    const VERSION = '0.6.6';
    private $_options, $_connection, $_serverProfile, $_responseReader;

    public function __construct($parameters = null, $clientOptions = null) {
        $this->setupClient($clientOptions !== null ? $clientOptions : new Predis_ClientOptions());
        $this->setupConnection($parameters);
    }

    public static function create(/* arguments */) {
        $argv = func_get_args();
        $argc = func_num_args();

        $options = null;
        $lastArg = $argv[$argc-1];
        if ($argc > 0 && !is_string($lastArg) && ($lastArg instanceof Predis_ClientOptions ||
            is_subclass_of($lastArg, 'Predis_RedisServerProfile'))) {
            $options = array_pop($argv);
            $argc--;
        }

        if ($argc === 0) {
            throw new Predis_ClientException('Missing connection parameters');
        }

        return new Predis_Client($argc === 1 ? $argv[0] : $argv, $options);
    }

    private static function filterClientOptions($options) {
        if ($options instanceof Predis_ClientOptions) {
            return $options;
        }
        if (is_array($options)) {
            return new Predis_ClientOptions($options);
        }
        if ($options instanceof Predis_RedisServerProfile) {
            return new Predis_ClientOptions(array(
                'profile' => $options
            ));
        }
        if (is_string($options)) {
            return new Predis_ClientOptions(array(
                'profile' => Predis_RedisServerProfile::get($options)
            ));
        }
        throw new InvalidArgumentException("Invalid type for client options");
    }

    private function setupClient($options) {
        $options = self::filterClientOptions($options);

        $this->setProfile($options->profile);

        $reader = $options->reader;
        $reader->setOption('iterable_multibulk', $options->iterable_multibulk);
        $reader->setOption('throw_on_error', $options->throw_on_error);

        $this->_options = $options;
        $this->_responseReader = $reader;
    }

    private function setupConnection($parameters) {
        if ($parameters !== null && !(is_array($parameters) || is_string($parameters))) {
            throw new Predis_ClientException('Invalid parameters type (array or string expected)');
        }
        if (is_array($parameters) && isset($parameters[0])) {
            $cluster = new Predis_ConnectionCluster($this->_options->key_distribution);
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
        if (!$parameters instanceof Predis_ConnectionParameters) {
            $parameters = new Predis_ConnectionParameters($parameters);
        }

        $connection = new Predis_Connection($parameters, $this->_responseReader);
        if ($parameters->password !== null) {
            $connection->pushInitCommand($this->createCommand(
                'auth', array($parameters->password)
            ));
        }
        if ($parameters->database !== null) {
            $connection->pushInitCommand($this->createCommand(
                'select', array($parameters->database)
            ));
        }

        return $connection;
    }

    private function setConnection(Predis_IConnection $connection) {
        $this->_connection = $connection;
    }

    public function setProfile($serverProfile) {
        if ($serverProfile instanceof Predis_RedisServerProfile) {
            $this->_serverProfile = $serverProfile;
        }
        else if (is_string($serverProfile)) {
            $this->_serverProfile = Predis_RedisServerProfile::get($serverProfile);
        }
        else {
            throw new InvalidArgumentException(
                "Invalid type for server profile, Predis_RedisServerProfile or string expected"
            );
        }
    }

    public function getProfile() {
        return $this->_serverProfile;
    }

    public function getResponseReader() {
        return $this->_responseReader;
    }

    public function getClientFor($connectionAlias) {
        if (!Predis_Shared_Utils::isCluster($this->_connection)) {
            throw new Predis_ClientException(
                'This method is supported only when the client is connected to a cluster of connections'
            );
        }

        $connection = $this->_connection->getConnectionById($connectionAlias);
        if ($connection === null) {
            throw new InvalidArgumentException(
                "Invalid connection alias: '$connectionAlias'"
            );
        }

        $newClient = new Predis_Client();
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
            return Predis_Shared_Utils::isCluster($this->_connection) 
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

    public function executeCommand(Predis_Command $command) {
        return $this->_connection->executeCommand($command);
    }

    public function executeCommandOnShards(Predis_Command $command) {
        $replies = array();
        if (Predis_Shared_Utils::isCluster($this->_connection)) {
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
        if (Predis_Shared_Utils::isCluster($this->_connection)) {
            throw new Predis_ClientException('Cannot send raw commands when connected to a cluster of Redis servers');
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
        $args = func_get_args();
        return $this->sharedInitializer($args, 'initPipeline');
    }

    public function pipelineSafe($pipelineBlock = null) {
        return $this->initPipeline(array('safe' => true), $pipelineBlock);
    }

    private function initPipeline(Array $options = null, $pipelineBlock = null) {
        $pipeline = null;
        if (isset($options)) {
            if (isset($options['safe']) && $options['safe'] == true) {
                $connection = $this->getConnection();
                $pipeline   = new Predis_CommandPipeline($this, $connection instanceof Predis_Connection
                    ? new Predis_Pipeline_SafeExecutor($connection)
                    : new Predis_Pipeline_SafeClusterExecutor($connection)
                );
            }
            else {
                $pipeline = new Predis_CommandPipeline($this);
            }
        }
        else {
            $pipeline = new Predis_CommandPipeline($this);
        }
        return $this->pipelineExecute($pipeline, $pipelineBlock);
    }

    private function pipelineExecute(Predis_CommandPipeline $pipeline, $block) {
        return $block !== null ? $pipeline->execute($block) : $pipeline;
    }

    public function multiExec(/* arguments */) {
        $args = func_get_args();
        return $this->sharedInitializer($args, 'initMultiExec');
    }

    private function initMultiExec(Array $options = null, $transBlock = null) {
        $multi = isset($options) ? new Predis_MultiExecBlock($this, $options) : new Predis_MultiExecBlock($this);
        return $transBlock !== null ? $multi->execute($transBlock) : $multi;
    }

    public function pubSubContext(Array $options = null) {
        return new Predis_PubSubContext($this, $options);
    }
}

/* ------------------------------------------------------------------------- */

interface Predis_IClientOptionsHandler {
    public function validate($option, $value);
    public function getDefault();
}

class Predis_ClientOptionsProfile implements Predis_IClientOptionsHandler {
    public function validate($option, $value) {
        if ($value instanceof Predis_RedisServerProfile) {
            return $value;
        }
        if (is_string($value)) {
            return Predis_RedisServerProfile::get($value);
        }
        throw new InvalidArgumentException("Invalid value for option $option");
    }

    public function getDefault() {
        return Predis_RedisServerProfile::getDefault();
    }
}

class Predis_ClientOptionsKeyDistribution implements Predis_IClientOptionsHandler {
    public function validate($option, $value) {
        if ($value instanceof Predis_Distribution_IDistributionStrategy) {
            return $value;
        }
        if (is_string($value)) {
            $valueReflection = new ReflectionClass($value);
            if ($valueReflection->isSubclassOf('Predis_Distribution_IDistributionStrategy')) {
                return new $value;
            }
        }
        throw new InvalidArgumentException("Invalid value for option $option");
    }

    public function getDefault() {
        return new Predis_Distribution_HashRing();
    }
}

class Predis_ClientOptionsIterableMultiBulk implements Predis_IClientOptionsHandler {
    public function validate($option, $value) {
        return (bool) $value;
    }

    public function getDefault() {
        return false;
    }
}

class Predis_ClientOptionsThrowOnError implements Predis_IClientOptionsHandler {
    public function validate($option, $value) {
        return (bool) $value;
    }

    public function getDefault() {
        return true;
    }
}

class Predis_ClientOptionsReader implements Predis_IClientOptionsHandler {
    public function validate($option, $value) {
        if ($value instanceof Predis_IResponseReader) {
            return $value;
        }
        if (is_string($value)) {
            if ($value === 'composable') {
                return new Predis_ResponseReader();
            }
            $valueReflection = new ReflectionClass($value);
            if ($valueReflection->isSubclassOf('Predis_IResponseReader')) {
                return new $value;
            }
        }
        throw new InvalidArgumentException("Invalid value for option $option");
    }

    public function getDefault() {
        return new Predis_FastResponseReader();
    }
}

class Predis_ClientOptions {
    private static $_optionsHandlers;
    private $_options;

    public function __construct($options = null) {
        self::initializeOptionsHandlers();
        $this->initializeOptions($options !== null ? $options : array());
    }

    private static function initializeOptionsHandlers() {
        if (!isset(self::$_optionsHandlers)) {
            self::$_optionsHandlers = self::getOptionsHandlers();
        }
    }

    private static function getOptionsHandlers() {
        return array(
            'profile'    => new Predis_ClientOptionsProfile(),
            'key_distribution' => new Predis_ClientOptionsKeyDistribution(),
            'iterable_multibulk' => new Predis_ClientOptionsIterableMultiBulk(),
            'throw_on_error' => new Predis_ClientOptionsThrowOnError(),
            'reader' => new Predis_ClientOptionsReader(),
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

class Predis_Protocol {
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

abstract class Predis_Command {
    private $_hash;
    private $_arguments = array();

    public abstract function getCommandId();

    public abstract function serializeRequest($command, $arguments);

    public function canBeHashed() {
        return true;
    }

    public function getHash(Predis_Distribution_IDistributionStrategy $distributor) {
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

    public final function invoke() {
        return $this->serializeRequest($this->getCommandId(), $this->getArguments());
    }
}

abstract class Predis_InlineCommand extends Predis_Command {
    public function serializeRequest($command, $arguments) {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            $arguments[0] = implode($arguments[0], ' ');
        }
        return $command . (count($arguments) > 0
            ? ' ' . implode($arguments, ' ') . "\r\n" : "\r\n"
        );
    }
}

abstract class Predis_BulkCommand extends Predis_Command {
    public function serializeRequest($command, $arguments) {
        $data = array_pop($arguments);
        if (is_array($data)) {
            $data = implode($data, ' ');
        }
        return $command . ' ' . implode($arguments, ' ') . ' ' . strlen($data) . 
            "\r\n" . $data . "\r\n";
    }
}

abstract class Predis_MultiBulkCommand extends Predis_Command {
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

interface Predis_IResponseHandler {
    function handle(Predis_Connection $connection, $payload);
}

class Predis_ResponseStatusHandler implements Predis_IResponseHandler {
    public function handle(Predis_Connection $connection, $status) {
        if ($status === "OK") {
            return true;
        }
        if ($status === "QUEUED") {
            return new Predis_ResponseQueued();
        }
        return $status;
    }
}

class Predis_ResponseErrorHandler implements Predis_IResponseHandler {
    public function handle(Predis_Connection $connection, $errorMessage) {
        throw new Predis_ServerException(substr($errorMessage, 4));
    }
}

class Predis_ResponseErrorSilentHandler implements Predis_IResponseHandler {
    public function handle(Predis_Connection $connection, $errorMessage) {
        return new Predis_ResponseError(substr($errorMessage, 4));
    }
}

class Predis_ResponseBulkHandler implements Predis_IResponseHandler {
    public function handle(Predis_Connection $connection, $lengthString) {
        $length = (int) $lengthString;
        if ($length != $lengthString) {
            Predis_Shared_Utils::onCommunicationException(new Predis_MalformedServerResponse(
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

class Predis_ResponseMultiBulkHandler implements Predis_IResponseHandler {
    public function handle(Predis_Connection $connection, $lengthString) {
        $listLength = (int) $lengthString;
        if ($listLength != $lengthString) {
            Predis_Shared_Utils::onCommunicationException(new Predis_MalformedServerResponse(
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

class Predis_ResponseMultiBulkStreamHandler implements Predis_IResponseHandler {
    public function handle(Predis_Connection $connection, $lengthString) {
        $listLength = (int) $lengthString;
        if ($listLength != $lengthString) {
            Predis_Shared_Utils::onCommunicationException(new Predis_MalformedServerResponse(
                $connection, "Cannot parse '$lengthString' as data length"
            ));
        }
        return new Predis_Shared_MultiBulkResponseIterator($connection, $lengthString);
    }
}

class Predis_ResponseIntegerHandler implements Predis_IResponseHandler {
    public function handle(Predis_Connection $connection, $number) {
        if (is_numeric($number)) {
            return (int) $number;
        }
        else {
            if ($number !== 'nil') {
                Predis_Shared_Utils::onCommunicationException(new Predis_MalformedServerResponse(
                    $connection, "Cannot parse '$number' as numeric response"
                ));
            }
            return null;
        }
    }
}

interface Predis_IResponseReader {
    public function read(Predis_Connection $connection);
    public function setOption($option, $value);
    public function getOption($option);
}

class Predis_FastResponseReader implements Predis_IResponseReader {
    private $_iterableMultibulk, $_throwErrors;

    public function __construct() {
        $this->_iterableMultibulk = false;
        $this->_throwErrors = true;
    }

    public function read(Predis_Connection $connection) {
        $chunk = $connection->readLine();
        $prefix = $chunk[0];
        $payload = substr($chunk, 1);
        switch ($prefix) {
            case '+':    // inline
                switch ($payload) {
                    case 'OK':
                        return true;
                    case 'QUEUED':
                        return new Predis_ResponseQueued();
                    default:
                        return $payload;
                }

            case '$':    // bulk
                $size = (int) $payload;
                if ($size === -1) {
                    return null;
                }
                return substr($connection->readBytes($size + 2), 0, -2);

            case '*':    // multi bulk
                $count = (int) $payload;
                if ($count === -1) {
                    return null;
                }
                if ($this->_iterableMultibulk) {
                    return new Predis_Shared_MultiBulkResponseIterator($connection, $count);
                }
                $multibulk = array();
                for ($i = 0; $i < $count; $i++) {
                    $multibulk[$i] = $this->read($connection);
                }
                return $multibulk;

            case ':':    // integer
                return (int) $payload;

            case '-':    // error
                $errorMessage = substr($payload, 4);
                if ($this->_throwErrors) {
                    throw new Predis_ServerException($errorMessage);
                }
                return new Predis_ResponseError($errorMessage);

            default:
                throw new Predis_CommunicationException(
                    $connection, "Unknown prefix: '$prefix'"
                );
        }
    }

    public function setOption($option, $value) {
        switch ($option) {
            case 'iterable_multibulk':
                $this->_iterableMultibulk = (bool) $value;
                break;
            case 'throw_on_error':
                $this->_throwErrors = (bool) $value;
                break;
        }
    }

    public function getOption($option) {
        switch ($option) {
            case 'iterable_multibulk':
                return $this->_iterableMultibulk;
            case 'throw_on_error':
                return $this->_throwErrors;
        }
    }
}

class Predis_ResponseReader implements Predis_IResponseReader {
    private $_prefixHandlers;

    public function __construct() {
        $this->initializePrefixHandlers();
    }

    private function initializePrefixHandlers() {
        $this->_prefixHandlers = array(
            Predis_Protocol::PREFIX_STATUS     => new Predis_ResponseStatusHandler(), 
            Predis_Protocol::PREFIX_ERROR      => new Predis_ResponseErrorHandler(), 
            Predis_Protocol::PREFIX_INTEGER    => new Predis_ResponseIntegerHandler(), 
            Predis_Protocol::PREFIX_BULK       => new Predis_ResponseBulkHandler(), 
            Predis_Protocol::PREFIX_MULTI_BULK => new Predis_ResponseMultiBulkHandler(), 
        );
    }

    public function setHandler($prefix, Predis_IResponseHandler $handler) {
        $this->_prefixHandlers[$prefix] = $handler;
    }

    public function getHandler($prefix) {
        if (isset($this->_prefixHandlers[$prefix])) {
            return $this->_prefixHandlers[$prefix];
        }
    }

    public function read(Predis_Connection $connection) {
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

    private function throwMalformedResponse(Predis_Connection $connection, $message) {
        Predis_Shared_Utils::onCommunicationException(new Predis_MalformedServerResponse(
            $connection, $message
        ));
    }

    public function setOption($option, $value) {
        switch ($option) {
            case 'iterable_multibulk':
                $handler = $value ? 'Predis_ResponseMultiBulkStreamHandler' : 'Predis_ResponseMultiBulkHandler';
                $this->_prefixHandlers[Predis_Protocol::PREFIX_MULTI_BULK] = new $handler();
                break;
            case 'throw_on_error':
                $handler = $value ? 'Predis_ResponseErrorHandler' : 'Predis_ResponseErrorSilentHandler';
                $this->_prefixHandlers[Predis_Protocol::PREFIX_ERROR] = new $handler();
                break;
        }
    }

    public function getOption($option) {
        switch ($option) {
            case 'iterable_multibulk':
                return $this->_prefixHandlers[Predis_Protocol::PREFIX_MULTI_BULK] instanceof Predis_ResponseMultiBulkStreamHandler;
            case 'throw_on_error':
                return $this->_prefixHandlers[Predis_Protocol::PREFIX_ERROR] instanceof Predis_ResponseErrorHandler;
        }
    }
}

class Predis_ResponseError {
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

class Predis_ResponseQueued {
    public $skipParse = true;

    public function __toString() {
        return Predis_Protocol::QUEUED;
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

class Predis_CommandPipeline {
    private $_redisClient, $_pipelineBuffer, $_returnValues, $_running, $_executor;

    public function __construct(Predis_Client $redisClient, Predis_Pipeline_IPipelineExecutor $executor = null) {
        $this->_redisClient    = $redisClient;
        $this->_executor       = $executor !== null ? $executor : new Predis_Pipeline_StandardExecutor();
        $this->_pipelineBuffer = array();
        $this->_returnValues   = array();
    }

    public function __call($method, $arguments) {
        $command = $this->_redisClient->createCommand($method, $arguments);
        $this->recordCommand($command);
        return $this;
    }

    private function recordCommand(Predis_Command $command) {
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
            throw new Predis_ClientException("This pipeline is already opened");
        }
        $this->_running = $bool;
    }

    public function execute($block = null) {
        if ($block && !is_callable($block)) {
            throw new InvalidArgumentException('Argument passed must be a callable object');
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
        catch (Exception $exception) {
            $pipelineBlockException = $exception;
        }

        $this->setRunning(false);

        if ($pipelineBlockException !== null) {
            throw $pipelineBlockException;
        }

        return $this->_returnValues;
    }
}

class Predis_MultiExecBlock {
    private $_initialized, $_discarded, $_insideBlock, $_checkAndSet, $_watchedKeys;
    private $_redisClient, $_options, $_commands;
    private $_supportsWatch;

    public function __construct(Predis_Client $redisClient, Array $options = null) {
        $this->checkCapabilities($redisClient);
        $this->_options = isset($options) ? $options : array();
        $this->_redisClient = $redisClient;
        $this->reset();
    }

    private function checkCapabilities(Predis_Client $redisClient) {
        if (Predis_Shared_Utils::isCluster($redisClient->getConnection())) {
            throw new Predis_ClientException(
                'Cannot initialize a MULTI/EXEC context over a cluster of connections'
            );
        }
        $profile = $redisClient->getProfile();
        if ($profile->supportsCommands(array('multi', 'exec', 'discard')) === false) {
            throw new Predis_ClientException(
                'The current profile does not support MULTI, EXEC and DISCARD commands'
            );
        }
        $this->_supportsWatch = $profile->supportsCommands(array('watch', 'unwatch'));
    }

    private function isWatchSupported() {
        if ($this->_supportsWatch === false) {
            throw new Predis_ClientException(
                'The current profile does not support WATCH and UNWATCH commands'
            );
        }
    }

    private function reset() {
        $this->_initialized = false;
        $this->_discarded   = false;
        $this->_checkAndSet = false;
        $this->_insideBlock = false;
        $this->_watchedKeys = false;
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
        if (!$response instanceof Predis_ResponseQueued) {
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
            throw new Predis_ClientException('WATCH inside MULTI is not allowed');
        }
        $this->_watchedKeys = true;
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
        $this->_watchedKeys = false;
        $this->_redisClient->unwatch();
        return $this;
    }

    public function discard() {
        if ($this->_initialized === true) {
            $command = $this->_checkAndSet ? 'unwatch' : 'discard';
            $this->_redisClient->$command();
            $this->reset();
            $this->_discarded = true;
        }
        return $this;
    }

    public function exec() {
        return $this->execute();
    }

    private function checkBeforeExecution($block) {
        if ($this->_insideBlock === true) {
            throw new Predis_ClientException(
                "Cannot invoke 'execute' or 'exec' inside an active client transaction block"
            );
        }
        if ($block) {
            if (!is_callable($block)) {
                throw new InvalidArgumentException(
                    'Argument passed must be a callable object'
                );
            }
            if (count($this->_commands) > 0) {
                $this->discard();
                throw new Predis_ClientException(
                    'Cannot execute a transaction block after using fluent interface'
                );
            }
        }
        if (isset($this->_options['retry']) && !isset($block)) {
            $this->discard();
            throw new InvalidArgumentException(
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
                catch (Predis_CommunicationException $exception) {
                    $blockException = $exception;
                }
                catch (Predis_ServerException $exception) {
                    $blockException = $exception;
                }
                catch (Exception $exception) {
                    $blockException = $exception;
                    $this->discard();
                }
                $this->_insideBlock = false;
                if ($blockException !== null) {
                    throw $blockException;
                }
            }

            if (count($this->_commands) === 0) {
                if ($this->_watchedKeys) {
                    $this->discard();
                    return;
                }
                return;
            }

            $reply = $this->_redisClient->exec();
            if ($reply === null) {
                if ($attemptsLeft === 0) {
                    throw new Predis_AbortedMultiExec(
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

        $execReply = $reply instanceof Iterator ? iterator_to_array($reply) : $reply;
        $sizeofReplies = count($execReply);

        $commands = &$this->_commands;
        if ($sizeofReplies !== count($commands)) {
            $this->malformedServerResponse(
                'Unexpected number of responses for a MultiExecBlock'
            );
        }
        for ($i = 0; $i < $sizeofReplies; $i++) {
            $returnValues[] = $commands[$i]->parseResponse($execReply[$i] instanceof Iterator
                ? iterator_to_array($execReply[$i])
                : $execReply[$i]
            );
            unset($commands[$i]);
        }

        return $returnValues;
    }

    private function malformedServerResponse($message) {
        // Since a MULTI/EXEC block cannot be initialized over a clustered 
        // connection, we can safely assume that Predis_Client::getConnection() 
        // will always return an instance of Predis_Connection.
        Predis_Shared_Utils::onCommunicationException(
            new Predis_MalformedServerResponse(
                $this->_redisClient->getConnection(), $message
            )
        );
    }
}

class Predis_PubSubContext implements Iterator {
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

    public function __construct(Predis_Client $redisClient, Array $options = null) {
        $this->checkCapabilities($redisClient);
        $this->_options = isset($options) ? $options : array();
        $this->_redisClient = $redisClient;
        $this->_statusFlags = self::STATUS_VALID;

        $this->genericSubscribeInit('subscribe');
        $this->genericSubscribeInit('psubscribe');
    }

    public function __destruct() {
        if ($this->valid()) {
            $this->closeContext();
        }
    }

    private function checkCapabilities(Predis_Client $redisClient) {
        if (Predis_Shared_Utils::isCluster($redisClient->getConnection())) {
            throw new Predis_ClientException(
                'Cannot initialize a PUB/SUB context over a cluster of connections'
            );
        }
        $profile = $redisClient->getProfile();
        $commands = array('publish', 'subscribe', 'unsubscribe', 'psubscribe', 'punsubscribe');
        if ($profile->supportsCommands($commands) === false) {
            throw new Predis_ClientException(
                'The current profile does not support PUB/SUB related commands'
            );
        }
    }

    private function genericSubscribeInit($subscribeAction) {
        if (isset($this->_options[$subscribeAction])) {
            $this->$subscribeAction($this->_options[$subscribeAction]);
        }
    }

    private function isFlagSet($value) {
        return ($this->_statusFlags & $value) === $value;
    }

    public function subscribe(/* arguments */) {
        $args = func_get_args();
        $this->writeCommand(self::SUBSCRIBE, $args);
        $this->_statusFlags |= self::STATUS_SUBSCRIBED;
    }

    public function unsubscribe(/* arguments */) {
        $args = func_get_args();
        $this->writeCommand(self::UNSUBSCRIBE, $args);
    }

    public function psubscribe(/* arguments */) {
        $args = func_get_args();
        $this->writeCommand(self::PSUBSCRIBE, $args);
        $this->_statusFlags |= self::STATUS_PSUBSCRIBED;
    }

    public function punsubscribe(/* arguments */) {
        $args = func_get_args();
        $this->writeCommand(self::PUNSUBSCRIBE, $args);
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
                throw new Predis_ClientException(
                    "Received an unknown message type {$response[0]} inside of a pubsub context"
                );
        }
    }
}

/* ------------------------------------------------------------------------- */

class Predis_ConnectionParameters {
    const DEFAULT_SCHEME = 'redis';
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 6379;
    const DEFAULT_TIMEOUT = 5;

    private static $_defaultParameters = array(
        'scheme' => self::DEFAULT_SCHEME,
        'host' => self::DEFAULT_HOST,
        'port' => self::DEFAULT_PORT,
        'database' => null,
        'password' => null,
        'connection_async' => false,
        'connection_persistent' => false,
        'connection_timeout' => self::DEFAULT_TIMEOUT,
        'read_write_timeout' => null,
        'alias' => null,
        'weight' => null,
        'path' => null,
    );

    private $_parameters;

    public function __construct($parameters = null) {
        $parameters = $parameters !== null ? $parameters : array();
        $extractor = is_array($parameters) ? 'filter' : 'parseURI';
        $this->_parameters = $this->$extractor($parameters);
    }

    private function parseURI($uri) {
        if (stripos($uri, 'unix') === 0) {
            // Hack to support URIs for UNIX sockets with minimal effort.
            $uri = str_ireplace('unix:///', 'unix://localhost/', $uri);
        }
        if (($parsed = @parse_url($uri)) === false || !isset($parsed['host'])) {
            throw new Predis_ClientException("Invalid URI: $uri");
        }
        if (isset($parsed['query'])) {
            foreach (explode('&', $parsed['query']) as $kv) {
                @list($k, $v) = explode('=', $kv);
                $parsed[$k] = $v;
            }
            unset($parsed['query']);
        }
        return $this->filter($parsed);
    }

    private function filter($parameters) {
        return array_merge(self::$_defaultParameters, $parameters);
    }

    public function __get($parameter) {
        return $this->_parameters[$parameter];
    }

    public function __isset($parameter) {
        return isset($this->_parameters[$parameter]);
    }
}

interface Predis_IConnection {
    public function connect();
    public function disconnect();
    public function isConnected();
    public function writeCommand(Predis_Command $command);
    public function readResponse(Predis_Command $command);
    public function executeCommand(Predis_Command $command);
}

class Predis_Connection implements Predis_IConnection {
    private static $_allowedSchemes = array('redis', 'tcp', 'unix');
    private $_params, $_socket, $_initCmds, $_reader;

    public function __construct(Predis_ConnectionParameters $parameters, Predis_IResponseReader $reader = null) {
        if (!in_array($parameters->scheme, self::$_allowedSchemes)) {
            throw new InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
        $this->_params   = $parameters;
        $this->_initCmds = array();
        $this->_reader   = $reader !== null ? $reader : new Predis_FastResponseReader();
    }

    public function __destruct() {
        if (!$this->_params->connection_persistent) {
            $this->disconnect();
        }
    }

    public function isConnected() {
        return isset($this->_socket);
    }

    public function connect() {
        if ($this->isConnected()) {
            throw new Predis_ClientException('Connection already estabilished');
        }
        $initializer = "{$this->_params->scheme}StreamInitializer";
        $this->_socket = $this->$initializer($this->_params);
        if (count($this->_initCmds) > 0){
            $this->sendInitializationCommands();
        }
    }

    private function tcpStreamInitializer(Predis_ConnectionParameters $parameters) {
        return $this->redisStreamInitializer($parameters);
    }

    private function redisStreamInitializer(Predis_ConnectionParameters $parameters) {
        $uri = sprintf('tcp://%s:%d/', $parameters->host, $parameters->port);
        $connectFlags = STREAM_CLIENT_CONNECT;
        if ($parameters->connection_async) {
            $connectFlags |= STREAM_CLIENT_ASYNC_CONNECT;
        }
        if ($parameters->connection_persistent) {
            $connectFlags |= STREAM_CLIENT_PERSISTENT;
        }
        $socket = @stream_socket_client(
            $uri, $errno, $errstr, $parameters->connection_timeout, $connectFlags
        );

        if (!$socket) {
            $this->onCommunicationException(trim($errstr), $errno);
        }

        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;
            $timeoutSeconds  = floor($parameters->read_write_timeout);
            $timeoutUSeconds = ($rwtimeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($socket, $rwtimeout, $timeoutUSeconds);
        }
        return $socket;
    }

    private function unixStreamInitializer(Predis_ConnectionParameters $parameters) {
        $uri = sprintf('unix:///%s', $parameters->path);
        $connectFlags = STREAM_CLIENT_CONNECT;
        if ($parameters->connection_persistent) {
            $connectFlags |= STREAM_CLIENT_PERSISTENT;
        }
        $socket = @stream_socket_client(
            $uri, $errno, $errstr, $parameters->connection_timeout, $connectFlags
        );
        if (!$socket) {
            $this->onCommunicationException(trim($errstr), $errno);
        }
        return $socket;
    }

    public function disconnect() {
        if ($this->isConnected()) {
            fclose($this->_socket);
            unset($this->_socket);
        }
    }

    public function pushInitCommand(Predis_Command $command){
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
        Predis_Shared_Utils::onCommunicationException(
            new Predis_CommunicationException($this, $message, $code)
        );
    }

    public function writeCommand(Predis_Command $command) {
        $this->writeBytes($command->invoke());
    }

    public function readResponse(Predis_Command $command) {
        $response = $this->_reader->read($this);
        return isset($response->skipParse) ? $response : $command->parseResponse($response);
    }

    public function executeCommand(Predis_Command $command) {
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
            throw new InvalidArgumentException('Length parameter must be greater than 0');
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
        if (isset($this->_socket)) {
            return $this->_socket;
        }
        $this->connect();
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

class Predis_ConnectionCluster implements Predis_IConnection, IteratorAggregate {
    private $_pool, $_distributor;

    public function __construct(Predis_Distribution_IDistributionStrategy $distributor = null) {
        $this->_pool = array();
        $this->_distributor = $distributor !== null ? $distributor : new Predis_Distribution_HashRing();
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

    public function add(Predis_Connection $connection) {
        $parameters = $connection->getParameters();
        if (isset($parameters->alias)) {
            $this->_pool[$parameters->alias] = $connection;
        }
        else {
            $this->_pool[] = $connection;
        }
        $this->_distributor->add($connection, $parameters->weight);
    }

    public function getConnection(Predis_Command $command) {
        if ($command->canBeHashed() === false) {
            throw new Predis_ClientException(
                sprintf("Cannot send '%s' commands to a cluster of connections", $command->getCommandId())
            );
        }
        return $this->_distributor->get($command->getHash($this->_distributor));
    }

    public function getConnectionById($id = null) {
        $alias = $id !== null ? $id : 0;
        return isset($this->_pool[$alias]) ? $this->_pool[$alias] : null;
    }

    public function getIterator() {
        return new ArrayIterator($this->_pool);
    }

    public function writeCommand(Predis_Command $command) {
        $this->getConnection($command)->writeCommand($command);
    }

    public function readResponse(Predis_Command $command) {
        return $this->getConnection($command)->readResponse($command);
    }

    public function executeCommand(Predis_Command $command) {
        $connection = $this->getConnection($command);
        $connection->writeCommand($command);
        return $connection->readResponse($command);
    }
}

/* ------------------------------------------------------------------------- */

abstract class Predis_RedisServerProfile {
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
            '1.2'     => 'Predis_RedisServer_v1_2',
            '2.0'     => 'Predis_RedisServer_v2_0',
            '2.2'     => 'Predis_RedisServer_v2_2',
            'default' => 'Predis_RedisServer_v2_2',
            'dev'     => 'Predis_RedisServer_vNext',
        );
    }

    public static function registerProfile($profileClass, $aliases) {
        if (!isset(self::$_serverProfiles)) {
            self::$_serverProfiles = self::predisServerProfiles();
        }

        $profileReflection = new ReflectionClass($profileClass);

        if (!$profileReflection->isSubclassOf('Predis_RedisServerProfile')) {
            throw new Predis_ClientException("Cannot register '$profileClass' as it is not a valid profile class");
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
            throw new Predis_ClientException("Unknown server profile: $version");
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
            throw new Predis_ClientException("'$method' is not a registered Redis command");
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
        $commandReflection = new ReflectionClass($command);

        if (!$commandReflection->isSubclassOf('Predis_Command')) {
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

class Predis_RedisServer_v1_2 extends Predis_RedisServerProfile {
    public function getVersion() { return '1.2'; }
    public function getSupportedCommands() {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* miscellaneous commands */
            'ping'                      => 'Predis_Commands_Ping',
            'echo'                      => 'Predis_Commands_DoEcho',
            'auth'                      => 'Predis_Commands_Auth',

            /* connection handling */
            'quit'                      => 'Predis_Commands_Quit',

            /* commands operating on string values */
            'set'                       => 'Predis_Commands_Set',
            'setnx'                     => 'Predis_Commands_SetPreserve',
            'mset'                      => 'Predis_Commands_SetMultiple',
            'msetnx'                    => 'Predis_Commands_SetMultiplePreserve',
            'get'                       => 'Predis_Commands_Get',
            'mget'                      => 'Predis_Commands_GetMultiple',
            'getset'                    => 'Predis_Commands_GetSet',
            'incr'                      => 'Predis_Commands_Increment',
            'incrby'                    => 'Predis_Commands_IncrementBy',
            'decr'                      => 'Predis_Commands_Decrement',
            'decrby'                    => 'Predis_Commands_DecrementBy',
            'exists'                    => 'Predis_Commands_Exists',
            'del'                       => 'Predis_Commands_Delete',
            'type'                      => 'Predis_Commands_Type',

            /* commands operating on the key space */
            'keys'                      => 'Predis_Commands_Keys_v1_2',
            'randomkey'                 => 'Predis_Commands_RandomKey',
            'rename'                    => 'Predis_Commands_Rename',
            'renamenx'                  => 'Predis_Commands_RenamePreserve',
            'expire'                    => 'Predis_Commands_Expire',
            'expireat'                  => 'Predis_Commands_ExpireAt',
            'dbsize'                    => 'Predis_Commands_DatabaseSize',
            'ttl'                       => 'Predis_Commands_TimeToLive',

            /* commands operating on lists */
            'rpush'                     => 'Predis_Commands_ListPushTail',
            'lpush'                     => 'Predis_Commands_ListPushHead',
            'llen'                      => 'Predis_Commands_ListLength',
            'lrange'                    => 'Predis_Commands_ListRange',
            'ltrim'                     => 'Predis_Commands_ListTrim',
            'lindex'                    => 'Predis_Commands_ListIndex',
            'lset'                      => 'Predis_Commands_ListSet',
            'lrem'                      => 'Predis_Commands_ListRemove',
            'lpop'                      => 'Predis_Commands_ListPopFirst',
            'rpop'                      => 'Predis_Commands_ListPopLast',
            'rpoplpush'                 => 'Predis_Commands_ListPopLastPushHead',

            /* commands operating on sets */
            'sadd'                      => 'Predis_Commands_SetAdd',
            'srem'                      => 'Predis_Commands_SetRemove',
            'spop'                      => 'Predis_Commands_SetPop',
            'smove'                     => 'Predis_Commands_SetMove',
            'scard'                     => 'Predis_Commands_SetCardinality',
            'sismember'                 => 'Predis_Commands_SetIsMember',
            'sinter'                    => 'Predis_Commands_SetIntersection',
            'sinterstore'               => 'Predis_Commands_SetIntersectionStore',
            'sunion'                    => 'Predis_Commands_SetUnion',
            'sunionstore'               => 'Predis_Commands_SetUnionStore',
            'sdiff'                     => 'Predis_Commands_SetDifference',
            'sdiffstore'                => 'Predis_Commands_SetDifferenceStore',
            'smembers'                  => 'Predis_Commands_SetMembers',
            'srandmember'               => 'Predis_Commands_SetRandomMember',

            /* commands operating on sorted sets */
            'zadd'                      => 'Predis_Commands_ZSetAdd',
            'zincrby'                   => 'Predis_Commands_ZSetIncrementBy',
            'zrem'                      => 'Predis_Commands_ZSetRemove',
            'zrange'                    => 'Predis_Commands_ZSetRange',
            'zrevrange'                 => 'Predis_Commands_ZSetReverseRange',
            'zrangebyscore'             => 'Predis_Commands_ZSetRangeByScore',
            'zcard'                     => 'Predis_Commands_ZSetCardinality',
            'zscore'                    => 'Predis_Commands_ZSetScore',
            'zremrangebyscore'          => 'Predis_Commands_ZSetRemoveRangeByScore',

            /* multiple databases handling commands */
            'select'                    => 'Predis_Commands_SelectDatabase',
            'move'                      => 'Predis_Commands_MoveKey',
            'flushdb'                   => 'Predis_Commands_FlushDatabase',
            'flushall'                  => 'Predis_Commands_FlushAll',

            /* sorting */
            'sort'                      => 'Predis_Commands_Sort',

            /* remote server control commands */
            'info'                      => 'Predis_Commands_Info',
            'slaveof'                   => 'Predis_Commands_SlaveOf',

            /* persistence control commands */
            'save'                      => 'Predis_Commands_Save',
            'bgsave'                    => 'Predis_Commands_BackgroundSave',
            'lastsave'                  => 'Predis_Commands_LastSave',
            'shutdown'                  => 'Predis_Commands_Shutdown',
            'bgrewriteaof'              => 'Predis_Commands_BackgroundRewriteAppendOnlyFile',
        );
    }
}

class Predis_RedisServer_v2_0 extends Predis_RedisServerProfile {
    public function getVersion() { return '2.0'; }
    public function getSupportedCommands() {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* miscellaneous commands */
            'ping'                      => 'Predis_Commands_Ping',
            'echo'                      => 'Predis_Commands_DoEcho',
            'auth'                      => 'Predis_Commands_Auth',

            /* connection handling */
            'quit'                      => 'Predis_Commands_Quit',

            /* commands operating on string values */
            'set'                       => 'Predis_Commands_Set',
            'setnx'                     => 'Predis_Commands_SetPreserve',
            'mset'                      => 'Predis_Commands_SetMultiple',
            'msetnx'                    => 'Predis_Commands_SetMultiplePreserve',
            'get'                       => 'Predis_Commands_Get',
            'mget'                      => 'Predis_Commands_GetMultiple',
            'getset'                    => 'Predis_Commands_GetSet',
            'incr'                      => 'Predis_Commands_Increment',
            'incrby'                    => 'Predis_Commands_IncrementBy',
            'decr'                      => 'Predis_Commands_Decrement',
            'decrby'                    => 'Predis_Commands_DecrementBy',
            'exists'                    => 'Predis_Commands_Exists',
            'del'                       => 'Predis_Commands_Delete',
            'type'                      => 'Predis_Commands_Type',

            /* commands operating on the key space */
            'keys'                      => 'Predis_Commands_Keys',
            'randomkey'                 => 'Predis_Commands_RandomKey',
            'rename'                    => 'Predis_Commands_Rename',
            'renamenx'                  => 'Predis_Commands_RenamePreserve',
            'expire'                    => 'Predis_Commands_Expire',
            'expireat'                  => 'Predis_Commands_ExpireAt',
            'dbsize'                    => 'Predis_Commands_DatabaseSize',
            'ttl'                       => 'Predis_Commands_TimeToLive',

            /* commands operating on lists */
            'rpush'                     => 'Predis_Commands_ListPushTail',
            'lpush'                     => 'Predis_Commands_ListPushHead',
            'llen'                      => 'Predis_Commands_ListLength',
            'lrange'                    => 'Predis_Commands_ListRange',
            'ltrim'                     => 'Predis_Commands_ListTrim',
            'lindex'                    => 'Predis_Commands_ListIndex',
            'lset'                      => 'Predis_Commands_ListSet',
            'lrem'                      => 'Predis_Commands_ListRemove',
            'lpop'                      => 'Predis_Commands_ListPopFirst',
            'rpop'                      => 'Predis_Commands_ListPopLast',
            'rpoplpush'                 => 'Predis_Commands_ListPopLastPushHead',

            /* commands operating on sets */
            'sadd'                      => 'Predis_Commands_SetAdd',
            'srem'                      => 'Predis_Commands_SetRemove',
            'spop'                      => 'Predis_Commands_SetPop',
            'smove'                     => 'Predis_Commands_SetMove',
            'scard'                     => 'Predis_Commands_SetCardinality',
            'sismember'                 => 'Predis_Commands_SetIsMember',
            'sinter'                    => 'Predis_Commands_SetIntersection',
            'sinterstore'               => 'Predis_Commands_SetIntersectionStore',
            'sunion'                    => 'Predis_Commands_SetUnion',
            'sunionstore'               => 'Predis_Commands_SetUnionStore',
            'sdiff'                     => 'Predis_Commands_SetDifference',
            'sdiffstore'                => 'Predis_Commands_SetDifferenceStore',
            'smembers'                  => 'Predis_Commands_SetMembers',
            'srandmember'               => 'Predis_Commands_SetRandomMember',

            /* commands operating on sorted sets */
            'zadd'                      => 'Predis_Commands_ZSetAdd',
            'zincrby'                   => 'Predis_Commands_ZSetIncrementBy',
            'zrem'                      => 'Predis_Commands_ZSetRemove',
            'zrange'                    => 'Predis_Commands_ZSetRange',
            'zrevrange'                 => 'Predis_Commands_ZSetReverseRange',
            'zrangebyscore'             => 'Predis_Commands_ZSetRangeByScore',
            'zcard'                     => 'Predis_Commands_ZSetCardinality',
            'zscore'                    => 'Predis_Commands_ZSetScore',
            'zremrangebyscore'          => 'Predis_Commands_ZSetRemoveRangeByScore',

            /* multiple databases handling commands */
            'select'                    => 'Predis_Commands_SelectDatabase',
            'move'                      => 'Predis_Commands_MoveKey',
            'flushdb'                   => 'Predis_Commands_FlushDatabase',
            'flushall'                  => 'Predis_Commands_FlushAll',

            /* sorting */
            'sort'                      => 'Predis_Commands_Sort',

            /* remote server control commands */
            'info'                      => 'Predis_Commands_Info',
            'slaveof'                   => 'Predis_Commands_SlaveOf',

            /* persistence control commands */
            'save'                      => 'Predis_Commands_Save',
            'bgsave'                    => 'Predis_Commands_BackgroundSave',
            'lastsave'                  => 'Predis_Commands_LastSave',
            'shutdown'                  => 'Predis_Commands_Shutdown',
            'bgrewriteaof'              => 'Predis_Commands_BackgroundRewriteAppendOnlyFile',


            /* ---------------- Redis 2.0 ---------------- */

            /* transactions */
            'multi'                     => 'Predis_Commands_Multi',
            'exec'                      => 'Predis_Commands_Exec',
            'discard'                   => 'Predis_Commands_Discard',

            /* commands operating on string values */
            'setex'                     => 'Predis_Commands_SetExpire',
            'append'                    => 'Predis_Commands_Append',
            'substr'                    => 'Predis_Commands_Substr',

            /* commands operating on lists */
            'blpop'                     => 'Predis_Commands_ListPopFirstBlocking',
            'brpop'                     => 'Predis_Commands_ListPopLastBlocking',

            /* commands operating on sorted sets */
            'zunionstore'               => 'Predis_Commands_ZSetUnionStore',
            'zinterstore'               => 'Predis_Commands_ZSetIntersectionStore',
            'zcount'                    => 'Predis_Commands_ZSetCount',
            'zrank'                     => 'Predis_Commands_ZSetRank',
            'zrevrank'                  => 'Predis_Commands_ZSetReverseRank',
            'zremrangebyrank'           => 'Predis_Commands_ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'hset'                      => 'Predis_Commands_HashSet',
            'hsetnx'                    => 'Predis_Commands_HashSetPreserve',
            'hmset'                     => 'Predis_Commands_HashSetMultiple',
            'hincrby'                   => 'Predis_Commands_HashIncrementBy',
            'hget'                      => 'Predis_Commands_HashGet',
            'hmget'                     => 'Predis_Commands_HashGetMultiple',
            'hdel'                      => 'Predis_Commands_HashDelete',
            'hexists'                   => 'Predis_Commands_HashExists',
            'hlen'                      => 'Predis_Commands_HashLength',
            'hkeys'                     => 'Predis_Commands_HashKeys',
            'hvals'                     => 'Predis_Commands_HashValues',
            'hgetall'                   => 'Predis_Commands_HashGetAll',

            /* publish - subscribe */
            'subscribe'                 => 'Predis_Commands_Subscribe',
            'unsubscribe'               => 'Predis_Commands_Unsubscribe',
            'psubscribe'                => 'Predis_Commands_SubscribeByPattern',
            'punsubscribe'              => 'Predis_Commands_UnsubscribeByPattern',
            'publish'                   => 'Predis_Commands_Publish',

            /* remote server control commands */
            'config'                    => 'Predis_Commands_Config',
        );
    }
}

class Predis_RedisServer_v2_2 extends Predis_RedisServerProfile {
    public function getVersion() { return '2.2'; }
    public function getSupportedCommands() {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* miscellaneous commands */
            'ping'                      => 'Predis_Commands_Ping',
            'echo'                      => 'Predis_Commands_DoEcho',
            'auth'                      => 'Predis_Commands_Auth',

            /* connection handling */
            'quit'                      => 'Predis_Commands_Quit',

            /* commands operating on string values */
            'set'                       => 'Predis_Commands_Set',
            'setnx'                     => 'Predis_Commands_SetPreserve',
            'mset'                      => 'Predis_Commands_SetMultiple',
            'msetnx'                    => 'Predis_Commands_SetMultiplePreserve',
            'get'                       => 'Predis_Commands_Get',
            'mget'                      => 'Predis_Commands_GetMultiple',
            'getset'                    => 'Predis_Commands_GetSet',
            'incr'                      => 'Predis_Commands_Increment',
            'incrby'                    => 'Predis_Commands_IncrementBy',
            'decr'                      => 'Predis_Commands_Decrement',
            'decrby'                    => 'Predis_Commands_DecrementBy',
            'exists'                    => 'Predis_Commands_Exists',
            'del'                       => 'Predis_Commands_Delete',
            'type'                      => 'Predis_Commands_Type',

            /* commands operating on the key space */
            'keys'                      => 'Predis_Commands_Keys',
            'randomkey'                 => 'Predis_Commands_RandomKey',
            'rename'                    => 'Predis_Commands_Rename',
            'renamenx'                  => 'Predis_Commands_RenamePreserve',
            'expire'                    => 'Predis_Commands_Expire',
            'expireat'                  => 'Predis_Commands_ExpireAt',
            'dbsize'                    => 'Predis_Commands_DatabaseSize',
            'ttl'                       => 'Predis_Commands_TimeToLive',

            /* commands operating on lists */
            'rpush'                     => 'Predis_Commands_ListPushTail',
            'lpush'                     => 'Predis_Commands_ListPushHead',
            'llen'                      => 'Predis_Commands_ListLength',
            'lrange'                    => 'Predis_Commands_ListRange',
            'ltrim'                     => 'Predis_Commands_ListTrim',
            'lindex'                    => 'Predis_Commands_ListIndex',
            'lset'                      => 'Predis_Commands_ListSet',
            'lrem'                      => 'Predis_Commands_ListRemove',
            'lpop'                      => 'Predis_Commands_ListPopFirst',
            'rpop'                      => 'Predis_Commands_ListPopLast',
            'rpoplpush'                 => 'Predis_Commands_ListPopLastPushHead',

            /* commands operating on sets */
            'sadd'                      => 'Predis_Commands_SetAdd',
            'srem'                      => 'Predis_Commands_SetRemove',
            'spop'                      => 'Predis_Commands_SetPop',
            'smove'                     => 'Predis_Commands_SetMove',
            'scard'                     => 'Predis_Commands_SetCardinality',
            'sismember'                 => 'Predis_Commands_SetIsMember',
            'sinter'                    => 'Predis_Commands_SetIntersection',
            'sinterstore'               => 'Predis_Commands_SetIntersectionStore',
            'sunion'                    => 'Predis_Commands_SetUnion',
            'sunionstore'               => 'Predis_Commands_SetUnionStore',
            'sdiff'                     => 'Predis_Commands_SetDifference',
            'sdiffstore'                => 'Predis_Commands_SetDifferenceStore',
            'smembers'                  => 'Predis_Commands_SetMembers',
            'srandmember'               => 'Predis_Commands_SetRandomMember',

            /* commands operating on sorted sets */
            'zadd'                      => 'Predis_Commands_ZSetAdd',
            'zincrby'                   => 'Predis_Commands_ZSetIncrementBy',
            'zrem'                      => 'Predis_Commands_ZSetRemove',
            'zrange'                    => 'Predis_Commands_ZSetRange',
            'zrevrange'                 => 'Predis_Commands_ZSetReverseRange',
            'zrangebyscore'             => 'Predis_Commands_ZSetRangeByScore',
            'zcard'                     => 'Predis_Commands_ZSetCardinality',
            'zscore'                    => 'Predis_Commands_ZSetScore',
            'zremrangebyscore'          => 'Predis_Commands_ZSetRemoveRangeByScore',

            /* multiple databases handling commands */
            'select'                    => 'Predis_Commands_SelectDatabase',
            'move'                      => 'Predis_Commands_MoveKey',
            'flushdb'                   => 'Predis_Commands_FlushDatabase',
            'flushall'                  => 'Predis_Commands_FlushAll',

            /* sorting */
            'sort'                      => 'Predis_Commands_Sort',

            /* remote server control commands */
            'info'                      => 'Predis_Commands_Info',
            'slaveof'                   => 'Predis_Commands_SlaveOf',

            /* persistence control commands */
            'save'                      => 'Predis_Commands_Save',
            'bgsave'                    => 'Predis_Commands_BackgroundSave',
            'lastsave'                  => 'Predis_Commands_LastSave',
            'shutdown'                  => 'Predis_Commands_Shutdown',
            'bgrewriteaof'              => 'Predis_Commands_BackgroundRewriteAppendOnlyFile',


            /* ---------------- Redis 2.0 ---------------- */

            /* transactions */
            'multi'                     => 'Predis_Commands_Multi',
            'exec'                      => 'Predis_Commands_Exec',
            'discard'                   => 'Predis_Commands_Discard',

            /* commands operating on string values */
            'setex'                     => 'Predis_Commands_SetExpire',
            'append'                    => 'Predis_Commands_Append',
            'substr'                    => 'Predis_Commands_Substr',

            /* commands operating on lists */
            'blpop'                     => 'Predis_Commands_ListPopFirstBlocking',
            'brpop'                     => 'Predis_Commands_ListPopLastBlocking',

            /* commands operating on sorted sets */
            'zunionstore'               => 'Predis_Commands_ZSetUnionStore',
            'zinterstore'               => 'Predis_Commands_ZSetIntersectionStore',
            'zcount'                    => 'Predis_Commands_ZSetCount',
            'zrank'                     => 'Predis_Commands_ZSetRank',
            'zrevrank'                  => 'Predis_Commands_ZSetReverseRank',
            'zremrangebyrank'           => 'Predis_Commands_ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'hset'                      => 'Predis_Commands_HashSet',
            'hsetnx'                    => 'Predis_Commands_HashSetPreserve',
            'hmset'                     => 'Predis_Commands_HashSetMultiple',
            'hincrby'                   => 'Predis_Commands_HashIncrementBy',
            'hget'                      => 'Predis_Commands_HashGet',
            'hmget'                     => 'Predis_Commands_HashGetMultiple',
            'hdel'                      => 'Predis_Commands_HashDelete',
            'hexists'                   => 'Predis_Commands_HashExists',
            'hlen'                      => 'Predis_Commands_HashLength',
            'hkeys'                     => 'Predis_Commands_HashKeys',
            'hvals'                     => 'Predis_Commands_HashValues',
            'hgetall'                   => 'Predis_Commands_HashGetAll',

            /* publish - subscribe */
            'subscribe'                 => 'Predis_Commands_Subscribe',
            'unsubscribe'               => 'Predis_Commands_Unsubscribe',
            'psubscribe'                => 'Predis_Commands_SubscribeByPattern',
            'punsubscribe'              => 'Predis_Commands_UnsubscribeByPattern',
            'publish'                   => 'Predis_Commands_Publish',

            /* remote server control commands */
            'config'                    => 'Predis_Commands_Config',


            /* ---------------- Redis 2.2 ---------------- */

            /* transactions */
            'watch'                     => 'Predis_Commands_Watch',
            'unwatch'                   => 'Predis_Commands_Unwatch',

            /* commands operating on string values */
            'strlen'                    => 'Predis_Commands_Strlen',
            'setrange'                  => 'Predis_Commands_SetRange',
            'getrange'                  => 'Predis_Commands_Substr',
            'setbit'                    => 'Predis_Commands_SetBit',
            'getbit'                    => 'Predis_Commands_GetBit',

            /* commands operating on the key space */
            'persist'                   => 'Predis_Commands_Persist',

            /* commands operating on lists */
            'rpushx'                    => 'Predis_Commands_ListPushTailX',
            'lpushx'                    => 'Predis_Commands_ListPushHeadX',
            'linsert'                   => 'Predis_Commands_ListInsert',
            'brpoplpush'                => 'Predis_Commands_ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'zrevrangebyscore'          => 'Predis_Commands_ZSetReverseRangeByScore',
        );
    }
}

class Predis_RedisServer_vNext extends Predis_RedisServer_v2_2 {
    public function getVersion() { return 'DEV'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* remote server control commands */
            'info'                      => 'Predis_Commands_Info_v24',
        ));
    }
}

/* ------------------------------------------------------------------------- */

interface Predis_Pipeline_IPipelineExecutor {
    public function execute(Predis_IConnection $connection, &$commands);
}

class Predis_Pipeline_StandardExecutor implements Predis_Pipeline_IPipelineExecutor {
    public function execute(Predis_IConnection $connection, &$commands) {
        $sizeofPipe = count($commands);
        $values = array();

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }
        try {
            for ($i = 0; $i < $sizeofPipe; $i++) {
                $response = $connection->readResponse($commands[$i]);
                $values[] = $response instanceof Iterator
                    ? iterator_to_array($response)
                    : $response;
                unset($commands[$i]);
            }
        }
        catch (Predis_ServerException $exception) {
            // force disconnection to prevent protocol desynchronization
            $connection->disconnect();
            throw $exception;
        }

        return $values;
    }
}

class Predis_Pipeline_SafeExecutor implements Predis_Pipeline_IPipelineExecutor {
    public function execute(Predis_IConnection $connection, &$commands) {
        $sizeofPipe = count($commands);
        $values = array();

        foreach ($commands as $command) {
            try {
                $connection->writeCommand($command);
            }
            catch (Predis_CommunicationException $exception) {
                return array_fill(0, $sizeofPipe, $exception);
            }
        }

        for ($i = 0; $i < $sizeofPipe; $i++) {
            $command = $commands[$i];
            unset($commands[$i]);
            try {
                $response = $connection->readResponse($command);
                $values[] = ($response instanceof Iterator
                    ? iterator_to_array($response)
                    : $response
                );
            }
            catch (Predis_ServerException $exception) {
                $values[] = $exception->toResponseError();
            }
            catch (Predis_CommunicationException $exception) {
                $toAdd  = count($commands) - count($values);
                $values = array_merge($values, array_fill(0, $toAdd, $exception));
                break;
            }
        }

        return $values;
    }
}

class Predis_Pipeline_SafeClusterExecutor implements Predis_Pipeline_IPipelineExecutor {
    public function execute(Predis_IConnection $connection, &$commands) {
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
            catch (Predis_CommunicationException $exception) {
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
                $values[] = ($response instanceof Iterator
                    ? iterator_to_array($response)
                    : $response
                );
            }
            catch (Predis_ServerException $exception) {
                $values[] = $exception->toResponseError();
            }
            catch (Predis_CommunicationException $exception) {
                $values[] = $exception;
                $connectionExceptions[$connectionObjectHash] = $exception;
            }
        }

        return $values;
    }
}

/* ------------------------------------------------------------------------- */

interface Predis_Distribution_IDistributionStrategy {
    public function add($node, $weight = null);
    public function remove($node);
    public function get($key);
    public function generateKey($value);
}

class Predis_Distribution_EmptyRingException extends Exception { }

class Predis_Distribution_HashRing implements Predis_Distribution_IDistributionStrategy {
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
        // TODO: self::DEFAULT_WEIGHT does not work for inherited classes that 
        //       override the DEFAULT_WEIGHT constant.
        $this->_nodes[] = array(
            'object' => $node, 
            'weight' => (int) ($weight !== null ? $weight : self::DEFAULT_WEIGHT), 
        );
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
            throw new Predis_Distribution_EmptyRingException('Cannot initialize empty hashring');
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

class Predis_Distribution_KetamaPureRing extends Predis_Distribution_HashRing {
    const DEFAULT_REPLICAS = 160;

    public function __construct() {
        parent::__construct(self::DEFAULT_REPLICAS);
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

class Predis_Shared_Utils {
    public static function isCluster(Predis_IConnection $connection) {
        return $connection instanceof Predis_ConnectionCluster;
    }

    public static function onCommunicationException(Predis_CommunicationException $exception) {
        if ($exception->shouldResetConnection()) {
            $connection = $exception->getConnection();
            if ($connection->isConnected()) {
                $connection->disconnect();
            }
        }
        throw $exception;
    }

    public static function filterArrayArguments(Array $arguments) {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            return $arguments[0];
        }
        return $arguments;
    }
}

abstract class Predis_Shared_MultiBulkResponseIteratorBase implements Iterator, Countable {
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

class Predis_Shared_MultiBulkResponseIterator extends Predis_Shared_MultiBulkResponseIteratorBase {
    private $_connection;

    public function __construct(Predis_Connection $connection, $size) {
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

class Predis_Shared_MultiBulkResponseKVIterator extends Predis_Shared_MultiBulkResponseIteratorBase {
    private $_iterator;

    public function __construct(Predis_Shared_MultiBulkResponseIterator $iterator) {
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

/* miscellaneous commands */
class Predis_Commands_Ping extends  Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG';
    }
}

class Predis_Commands_DoEcho extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}

class Predis_Commands_Auth extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}

/* connection handling */
class Predis_Commands_Quit extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}

/* commands operating on string values */
class Predis_Commands_Set extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SET'; }
}

class Predis_Commands_SetExpire extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SETEX'; }
}

class Predis_Commands_SetPreserve extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetMultiple extends Predis_MultiBulkCommand {
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

class Predis_Commands_SetMultiplePreserve extends Predis_Commands_SetMultiple {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_Get extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'GET'; }
}

class Predis_Commands_GetMultiple extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
    public function filterArguments(Array $arguments) {
        return Predis_Shared_Utils::filterArrayArguments($arguments);
    }
}

class Predis_Commands_GetSet extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'GETSET'; }
}

class Predis_Commands_Increment extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'INCR'; }
}

class Predis_Commands_IncrementBy extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'INCRBY'; }
}

class Predis_Commands_Decrement extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'DECR'; }
}

class Predis_Commands_DecrementBy extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'DECRBY'; }
}

class Predis_Commands_Exists extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_Delete extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'DEL'; }
}

class Predis_Commands_Type extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'TYPE'; }
}

class Predis_Commands_Append extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'APPEND'; }
}

class Predis_Commands_SetRange extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SETRANGE'; }
}

class Predis_Commands_Substr extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SUBSTR'; }
}

class Predis_Commands_SetBit extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SETBIT'; }
}

class Predis_Commands_GetBit extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'GETBIT'; }
}

class Predis_Commands_Strlen extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'STRLEN'; }
}

/* commands operating on the key space */
class Predis_Commands_Keys extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
}

class Predis_Commands_Keys_v1_2 extends Predis_Commands_Keys {
    public function parseResponse($data) {
        return explode(' ', $data);
    }
}

class Predis_Commands_RandomKey extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}

class Predis_Commands_Rename extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}

class Predis_Commands_RenamePreserve extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_Expire extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_ExpireAt extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_Persist extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'PERSIST'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_DatabaseSize extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}

class Predis_Commands_TimeToLive extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'TTL'; }
}

/* commands operating on lists */
class Predis_Commands_ListPushTail extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'RPUSH'; }
}

class Predis_Commands_ListPushTailX extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'RPUSHX'; }
}

class Predis_Commands_ListPushHead extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LPUSH'; }
}

class Predis_Commands_ListPushHeadX extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LPUSHX'; }
}

class Predis_Commands_ListLength extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LLEN'; }
}

class Predis_Commands_ListRange extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LRANGE'; }
}

class Predis_Commands_ListTrim extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LTRIM'; }
}

class Predis_Commands_ListIndex extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LINDEX'; }
}

class Predis_Commands_ListSet extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LSET'; }
}

class Predis_Commands_ListRemove extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LREM'; }
}

class Predis_Commands_ListPopLastPushHead extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'RPOPLPUSH'; }
}

class Predis_Commands_ListPopLastPushHeadBlocking extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'BRPOPLPUSH'; }
}

class Predis_Commands_ListPopFirst extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LPOP'; }
}

class Predis_Commands_ListPopLast extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'RPOP'; }
}

class Predis_Commands_ListPopFirstBlocking extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'BLPOP'; }
}

class Predis_Commands_ListPopLastBlocking extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'BRPOP'; }
}

class Predis_Commands_ListInsert extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'LINSERT'; }
}

/* commands operating on sets */
class Predis_Commands_SetAdd extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetRemove extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetPop  extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SPOP'; }
}

class Predis_Commands_SetMove extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetCardinality extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SCARD'; }
}

class Predis_Commands_SetIsMember extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetIntersection extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SINTER'; }
    public function filterArguments(Array $arguments) {
        return Predis_Shared_Utils::filterArrayArguments($arguments);
    }
}

class Predis_Commands_SetIntersectionStore extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SINTERSTORE'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge(array($arguments[0]), $arguments[1]);
        }
        return $arguments;
    }
}

class Predis_Commands_SetUnion extends Predis_Commands_SetIntersection {
    public function getCommandId() { return 'SUNION'; }
}

class Predis_Commands_SetUnionStore extends Predis_Commands_SetIntersectionStore {
    public function getCommandId() { return 'SUNIONSTORE'; }
}

class Predis_Commands_SetDifference extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SDIFF'; }
}

class Predis_Commands_SetDifferenceStore extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SDIFFSTORE'; }
}

class Predis_Commands_SetMembers extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SMEMBERS'; }
}

class Predis_Commands_SetRandomMember extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'SRANDMEMBER'; }
}

/* commands operating on sorted sets */
class Predis_Commands_ZSetAdd extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_ZSetIncrementBy extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZINCRBY'; }
}

class Predis_Commands_ZSetRemove extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_ZSetUnionStore extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZUNIONSTORE'; }
    public function filterArguments(Array $arguments) {
        $options = array();
        $argc = count($arguments);
        if ($argc > 2 && is_array($arguments[$argc - 1])) {
            $options = $this->prepareOptions(array_pop($arguments));
        }
        if (is_array($arguments[1])) {
            $arguments = array_merge(
                array($arguments[0], count($arguments[1])),
                $arguments[1]
            );
        }
        return array_merge($arguments, $options);
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

class Predis_Commands_ZSetIntersectionStore extends Predis_Commands_ZSetUnionStore {
    public function getCommandId() { return 'ZINTERSTORE'; }
}

class Predis_Commands_ZSetRange extends Predis_MultiBulkCommand {
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
            if ($data instanceof Iterator) {
                return new Predis_Shared_MultiBulkResponseKVIterator($data);
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

class Predis_Commands_ZSetReverseRange extends Predis_Commands_ZSetRange {
    public function getCommandId() { return 'ZREVRANGE'; }
}

class Predis_Commands_ZSetRangeByScore extends Predis_Commands_ZSetRange {
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

class Predis_Commands_ZSetReverseRangeByScore extends Predis_Commands_ZSetRangeByScore {
    public function getCommandId() { return 'ZREVRANGEBYSCORE'; }
}

class Predis_Commands_ZSetCount extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZCOUNT'; }
}

class Predis_Commands_ZSetCardinality extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZCARD'; }
}

class Predis_Commands_ZSetScore extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZSCORE'; }
}

class Predis_Commands_ZSetRemoveRangeByScore extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZREMRANGEBYSCORE'; }
}

class Predis_Commands_ZSetRank extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZRANK'; }
}

class Predis_Commands_ZSetReverseRank extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZREVRANK'; }
}

class Predis_Commands_ZSetRemoveRangeByRank extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'ZREMRANGEBYRANK'; }
}

/* commands operating on hashes */
class Predis_Commands_HashSet extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HSET'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_HashSetPreserve extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_HashSetMultiple extends Predis_MultiBulkCommand {
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

class Predis_Commands_HashIncrementBy extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HINCRBY'; }
}

class Predis_Commands_HashGet extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HGET'; }
}

class Predis_Commands_HashGetMultiple extends Predis_MultiBulkCommand {
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

class Predis_Commands_HashDelete extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HDEL'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_HashExists extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HEXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_HashLength extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HLEN'; }
}

class Predis_Commands_HashKeys extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HKEYS'; }
}

class Predis_Commands_HashValues extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HVALS'; }
}

class Predis_Commands_HashGetAll extends Predis_MultiBulkCommand {
    public function getCommandId() { return 'HGETALL'; }
    public function parseResponse($data) {
        if ($data instanceof Iterator) {
            return new Predis_Shared_MultiBulkResponseKVIterator($data);
        }
        $result = array();
        for ($i = 0; $i < count($data); $i++) {
            $result[$data[$i]] = $data[++$i];
        }
        return $result;
    }
}

/* multiple databases handling commands */
class Predis_Commands_SelectDatabase extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SELECT'; }
}

class Predis_Commands_MoveKey extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_FlushDatabase extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}

class Predis_Commands_FlushAll extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}

/* sorting */
class Predis_Commands_Sort extends Predis_MultiBulkCommand {
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
class Predis_Commands_Multi extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MULTI'; }
}

class Predis_Commands_Exec extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'EXEC'; }
}

class Predis_Commands_Discard extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DISCARD'; }
}

/* publish/subscribe */
class Predis_Commands_Subscribe extends Predis_MultiBulkCommand {
    public function canBeHashed() { return false; }
    public function getCommandId() { return 'SUBSCRIBE'; }
    public function filterArguments(Array $arguments) {
        return Predis_Shared_Utils::filterArrayArguments($arguments);
    }
}

class Predis_Commands_Unsubscribe extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNSUBSCRIBE'; }
}

class Predis_Commands_SubscribeByPattern extends Predis_MultiBulkCommand {
    public function canBeHashed() { return false; }
    public function getCommandId() { return 'UNSUBSCRIBE'; }
    public function filterArguments(Array $arguments) {
        return Predis_Shared_Utils::filterArrayArguments($arguments);
    }
}

class Predis_Commands_UnsubscribeByPattern extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PUNSUBSCRIBE'; }
}

class Predis_Commands_Publish extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PUBLISH'; }
}

class Predis_Commands_Watch extends Predis_MultiBulkCommand {
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

class Predis_Commands_Unwatch extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNWATCH'; }
    public function parseResponse($data) { return (bool) $data; }
}

/* persistence control commands */
class Predis_Commands_Save extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}

class Predis_Commands_BackgroundSave extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGSAVE'; }
    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}

class Predis_Commands_BackgroundRewriteAppendOnlyFile extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGREWRITEAOF'; }
    public function parseResponse($data) {
        return $data == 'Background append only file rewriting started';
    }
}

class Predis_Commands_LastSave extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}

class Predis_Commands_Shutdown extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}

/* remote server control commands */
class Predis_Commands_Info extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'INFO'; }
    public function parseResponse($data) {
        $info      = array();
        $infoLines = explode("\r\n", $data, -1);
        foreach ($infoLines as $row) {
            @list($k, $v) = explode(':', $row);
            if ($row === '' || !isset($v)) {
                continue;
            }
            if (!preg_match('/^db\d+$/', $k)) {
                if ($k === 'allocation_stats') {
                    $info[$k] = $this->parseAllocationStats($v);
                    continue;
                }
                $info[$k] = $v;
            }
            else {
                $info[$k] = $this->parseDatabaseStats($v);
            }
        }
        return $info;
    }
    protected function parseDatabaseStats($str) {
        $db = array();
        foreach (explode(',', $str) as $dbvar) {
            list($dbvk, $dbvv) = explode('=', $dbvar);
            $db[trim($dbvk)] = $dbvv;
        }
        return $db;
    }
    protected function parseAllocationStats($str) {
        $stats = array();
        foreach (explode(',', $str) as $kv) {
            @list($size, $objects, $extra) = explode('=', $kv);
            // hack to prevent incorrect values when parsing the >=256 key
            if (isset($extra)) {
                $size = ">=$objects";
                $objects = $extra;
            }
            $stats[$size] = $objects;
        }
        return $stats;
    }
}

class Predis_Commands_Info_v24 extends Predis_Commands_Info {
    public function parseResponse($data) {
        $info      = array();
        $current   = null;
        $infoLines = explode("\r\n", $data, -1);
        foreach ($infoLines as $row) {
            if ($row === '') {
                continue;
            }
            if (preg_match('/^# (\w+)$/', $row, $matches)) {
                $info[$matches[1]] = array();
                $current = &$info[$matches[1]];
                continue;
            }
            list($k, $v) = explode(':', $row);
            if (!preg_match('/^db\d+$/', $k)) {
                if ($k === 'allocation_stats') {
                    $current[$k] = $this->parseAllocationStats($v);
                    continue;
                }
                $current[$k] = $v;
            }
            else {
                $current[$k] = $this->parseDatabaseStats($v);
            }
        }
        return $info;
    }
}

class Predis_Commands_SlaveOf extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SLAVEOF'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }
        return $arguments;
    }
}

class Predis_Commands_Config extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'CONFIG'; }
}
?>
