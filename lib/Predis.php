<?php
namespace Predis;

use Predis\Network\IConnection;
use Predis\Network\IConnectionSingle;
use Predis\Network\IConnectionCluster;
use Predis\Network\ConnectionCluster;
use Predis\Profiles\ServerProfile;
use Predis\Profiles\IServerProfile;
use Predis\Pipeline\IPipelineExecutor;
use Predis\Distribution\IDistributionStrategy;

class Client {
    private static $_connectionSchemes;
    private $_options, $_profile, $_connection;

    public function __construct($parameters = null, $options = null) {
        $this->_options = $this->filterOptions($options ?: new ClientOptions());
        $this->_profile = $this->_options->profile;
        $this->_connection = $this->initializeConnection($parameters);
    }

    private function filterOptions($options) {
        if ($options instanceof ClientOptions) {
            return $options;
        }
        if (is_array($options)) {
            return new ClientOptions($options);
        }
        if ($options instanceof IServerProfile) {
            return new ClientOptions(array('profile' => $options));
        }
        if (is_string($options)) {
            $profile = ServerProfile::get($options);
            return new ClientOptions(array('profile' => $profile));
        }
        throw new \InvalidArgumentException("Invalid type for client options");
    }

    private function initializeConnection($parameters = array()) {
        if (!isset($parameters)) {
            return $this->createConnection(array());
        }
        if ($parameters instanceof IConnection) {
            return $parameters;
        }
        if (is_array($parameters) && isset($parameters[0])) {
            $cluster = new ConnectionCluster($this->_options->key_distribution);
            foreach ($parameters as $single) {
                $cluster->add($single instanceof IConnectionSingle
                    ? $single : $this->createConnection($single)
                );
            }
            return $cluster;
        }
        return $this->createConnection($parameters);
    }

    private function createConnection($parameters) {
        if (is_array($parameters) || is_string($parameters)) {
            $parameters = new ConnectionParameters($parameters);
        }
        else if (!$parameters instanceof ConnectionParameters) {
            $type = is_object($parameters) ? get_class($parameters) : gettype($parameters);
            throw new \InvalidArgumentException(
                "Cannot create a connection using an argument of type $type"
            );
        }

        $options = $this->_options;
        $connection = self::newConnection($parameters);
        $protocol = $connection->getProtocol();
        $protocol->setOption('iterable_multibulk', $options->iterable_multibulk);
        $protocol->setOption('throw_on_error', $options->throw_on_error);
        $this->pushInitCommands($connection);

        $callback = $this->_options->on_connection_initialized;
        if (isset($callback)) {
            $callback($this, $connection);
        }

        return $connection;
    }

    private function pushInitCommands(IConnectionSingle $connection) {
        $params = $connection->getParameters();
        if (isset($params->password)) {
            $connection->pushInitCommand($this->createCommand(
                'auth', array($params->password)
            ));
        }
        if (isset($params->database)) {
            $connection->pushInitCommand($this->createCommand(
                'select', array($params->database)
            ));
        }
    }

    public function getProfile() {
        return $this->_profile;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function getClientFor($connectionAlias) {
        if (!Utils::isCluster($this->_connection)) {
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
        return new Client($connection, $this->_options);
    }

    public function connect() {
        if (!$this->_connection->isConnected()) {
            $this->_connection->connect();
        }
    }

    public function disconnect() {
        $this->_connection->disconnect();
    }

    public function isConnected() {
        return $this->_connection->isConnected();
    }

    public function getConnection($id = null) {
        $connection = $this->_connection;
        if (!isset($id)) {
            return $connection;
        }
        $isCluster = Utils::isCluster($connection);
        return $isCluster ? $connection->getConnectionById($id) : $connection;
    }

    public function __call($method, $arguments) {
        $command = $this->_profile->createCommand($method, $arguments);
        return $this->_connection->executeCommand($command);
    }

    public function createCommand($method, $arguments = array()) {
        return $this->_profile->createCommand($method, $arguments);
    }

    public function executeCommand(ICommand $command) {
        return $this->_connection->executeCommand($command);
    }

    public function executeCommandOnShards(ICommand $command) {
        if (Utils::isCluster($this->_connection)) {
            $replies = array();
            foreach ($this->_connection as $connection) {
                $replies[] = $connection->executeCommand($command);
            }
            return $replies;
        }
        return array($this->_connection->executeCommand($command));
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

    private function initPipeline(Array $options = null, $pipelineBlock = null) {
        $pipeline = null;
        if (isset($options)) {
            if (isset($options['safe']) && $options['safe'] == true) {
                $connection = $this->_connection;
                $pipeline = new CommandPipeline($this, 
                    Utils::isCluster($connection)
                        ? new Pipeline\SafeClusterExecutor($connection)
                        : new Pipeline\SafeExecutor($connection)
                );
            }
            else {
                $pipeline = new CommandPipeline($this);
            }
        }
        return $this->pipelineExecute(
            $pipeline ?: new CommandPipeline($this), $pipelineBlock
        );
    }

    private function pipelineExecute(CommandPipeline $pipeline, $block) {
        return $block !== null ? $pipeline->execute($block) : $pipeline;
    }

    public function multiExec(/* arguments */) {
        return $this->sharedInitializer(func_get_args(), 'initMultiExec');
    }

    private function initMultiExec(Array $options = null, $transBlock = null) {
        $multi = isset($options) ? new MultiExecContext($this, $options) : new MultiExecContext($this);
        return $transBlock !== null ? $multi->execute($transBlock) : $multi;
    }

    public function pubSubContext() {
        return new PubSubContext($this);
    }

    private static function ensureDefaultSchemes() {
        if (!isset(self::$_connectionSchemes)) {
            self::$_connectionSchemes = array(
                'tcp'   => '\Predis\Network\TcpConnection',
                'unix'  => '\Predis\Network\UnixDomainSocketConnection',
                // Compatibility with older versions.
                'redis' => '\Predis\Network\TcpConnection',
            );
        }
    }

    public static function registerScheme($scheme, $connectionClass) {
        self::ensureDefaultSchemes();
        $connectionReflection = new \ReflectionClass($connectionClass);
        if (!$connectionReflection->isSubclassOf('\Predis\Network\IConnectionSingle')) {
            throw new ClientException(
                "Cannot register '$connectionClass' as it is not a valid connection class"
            );
        }
        self::$_connectionSchemes[$scheme] = $connectionClass;
    }

    public static function getConnectionClass($scheme) {
        self::ensureDefaultSchemes();
        if (!isset(self::$_connectionSchemes[$scheme])) {
            throw new ClientException("Unknown connection scheme: $scheme");
        }
        return self::$_connectionSchemes[$scheme];
    }

    private static function newConnection(ConnectionParameters $parameters, IRedisProtocol $protocol = null) {
        $connection = self::getConnectionClass($parameters->scheme);
        return new $connection($parameters, $protocol);
    }

    public static function newConnectionByScheme($scheme, $parameters = array()) {
        $connection = self::getConnectionClass($scheme);
        if (!$parameters instanceof ConnectionParameters) {
            $parameters = new ConnectionParameters($parameters);
        }
        return self::newConnection($parameters);
    }
}

class CommandPipeline {
    private $_redisClient, $_pipelineBuffer, $_returnValues, $_running, $_executor;

    public function __construct(Client $redisClient, IPipelineExecutor $executor = null) {
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

    private function recordCommand(ICommand $command) {
        $this->_pipelineBuffer[] = $command;
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
        if ($bool === true && $this->_running === true) {
            throw new ClientException("This pipeline is already opened");
        }
        $this->_running = $bool;
    }

    public function execute($block = null) {
        if ($block && !is_callable($block)) {
            throw new \InvalidArgumentException('Argument passed must be a callable object');
        }

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

class MultiExecContext {
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
        if (Utils::isCluster($redisClient->getConnection())) {
            throw new ClientException(
                'Cannot initialize a MULTI/EXEC context over a cluster of connections'
            );
        }
        $profile = $redisClient->getProfile();
        if ($profile->supportsCommands(array('multi', 'exec', 'discard')) === false) {
            throw new ClientException(
                'The current profile does not support MULTI, EXEC and DISCARD commands'
            );
        }
        $this->_supportsWatch = $profile->supportsCommands(array('watch', 'unwatch'));
    }

    private function isWatchSupported() {
        if ($this->_supportsWatch === false) {
            throw new ClientException(
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
        $options = $this->_options;
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
        if (!isset($response->queued)) {
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
            throw new ClientException(
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
                'Unexpected number of responses for a MultiExecContext'
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
        Utils::onCommunicationException(new MalformedServerResponse(
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

    private $_redisClient, $_position;

    public function __construct(Client $redisClient) {
        $this->checkCapabilities($redisClient);
        $this->_redisClient = $redisClient;
        $this->_statusFlags = self::STATUS_VALID;
    }

    public function __destruct() {
        $this->closeContext();
    }

    private function checkCapabilities(Client $redisClient) {
        if (Utils::isCluster($redisClient->getConnection())) {
            throw new ClientException(
                'Cannot initialize a PUB/SUB context over a cluster of connections'
            );
        }
        $profile = $redisClient->getProfile();
        $commands = array('publish', 'subscribe', 'unsubscribe', 'psubscribe', 'punsubscribe');
        if ($profile->supportsCommands($commands) === false) {
            throw new ClientException(
                'The current profile does not support PUB/SUB related commands'
            );
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
        $connection = $this->_redisClient->getConnection();
        $protocol   = $connection->getProtocol();
        $response   = $protocol->read($connection);

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
                throw new ClientException(
                    "Received an unknown message type {$response[0]} inside of a pubsub context"
                );
        }
    }
}

/* -------------------------------------------------------------------------- */

abstract class PredisException extends \Exception {
    // Base Predis exception class
}

class ClientException extends PredisException {
    // Client-side errors
}

class AbortedMultiExec extends PredisException {
    // Aborted MULTI/EXEC transactions
}

class ServerException extends PredisException {
    // Server-side errors
    public function toResponseError() {
        return new ResponseError($this->getMessage());
    }
}

class CommunicationException extends PredisException {
    // Communication errors
    private $_connection;

    public function __construct(IConnectionSingle $connection, 
        $message = null, $code = null) {

        $this->_connection = $connection;
        parent::__construct($message, $code);
    }

    public function getConnection() { return $this->_connection; }
    public function shouldResetConnection() {  return true; }
}

class MalformedServerResponse extends CommunicationException {
    // Unexpected responses
}

/* -------------------------------------------------------------------------- */

interface ICommand {
    public function getCommandId();
    public function canBeHashed();
    public function closesConnection();
    public function getHash(IDistributionStrategy $distributor);
    public function setArgumentsArray(Array $arguments);
    public function getArguments();
    public function parseResponse($data);
}

abstract class Command implements ICommand {
    private $_hash;
    private $_arguments = array();

    public function canBeHashed() {
        return true;
    }

    public function getHash(IDistributionStrategy $distributor) {
        if (isset($this->_hash)) {
            return $this->_hash;
        }
        if (isset($this->_arguments[0])) {
            // TODO: should we throw an exception if the command does not
            // support sharding?
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
}

class ResponseError {
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
    public $queued = true;

    public function __toString() {
        return 'QUEUED';
    }
}

class ConnectionParameters {
    private $_parameters;
    private static $_sharedOptions;

    public function __construct($parameters = null) {
        $parameters = $parameters ?: array();
        $this->_parameters = is_array($parameters)
            ? $this->filter($parameters)
            : $this->parseURI($parameters);
    }

    private static function paramsExtractor($params, $kv) {
        @list($k, $v) = explode('=', $kv);
        $params[$k] = $v;
        return $params;
    }

    private static function getSharedOptions() {
        if (isset(self::$_sharedOptions)) {
            return self::$_sharedOptions;
        }

        $optEmpty   = new Options\Option();
        $optBoolean = new Options\CustomOption(array(
            'validate' => function($value) { return (bool) $value; },
            'default'  => function() { return false; },
        ));

        self::$_sharedOptions = array(
            'scheme' => new Options\CustomOption(array(
                'default'  => function() { return 'tcp'; },
            )),
            'host' => new Options\CustomOption(array(
                'default'  => function() { return '127.0.0.1'; },
            )),
            'port' => new Options\CustomOption(array(
                'validate' => function($value) { return (int) $value; },
                'default'  => function() { return 6379; },
            )),
            'path' => $optEmpty,
            'database' => $optEmpty,
            'password' => $optEmpty,
            'connection_async' => $optBoolean,
            'connection_persistent' => $optBoolean,
            'connection_timeout' => new Options\CustomOption(array(
                'default'  => function() { return 5; },
            )),
            'read_write_timeout' => $optEmpty,
            'alias' => $optEmpty,
            'weight' => $optEmpty,
        );

        return self::$_sharedOptions;
    }

    protected function parseURI($uri) {
        if (!is_string($uri)) {
            throw new \InvalidArgumentException('URI must be a string');
        }
        if (stripos($uri, 'unix') === 0) {
            // Hack to support URIs for UNIX sockets with minimal effort.
            $uri = str_ireplace('unix:///', 'unix://localhost/', $uri);
        }
        $parsed = @parse_url($uri);
        if ($parsed == false || !isset($parsed['host'])) {
            throw new ClientException("Invalid URI: $uri");
        }
        if (array_key_exists('query', $parsed)) {
            $query  = explode('&', $parsed['query']);
            $parsed = array_reduce($query, 'self::paramsExtractor', $parsed);
        }
        return $this->filter($parsed);
    }

    protected function filter($parameters) {
        $handlers = self::getSharedOptions();
        foreach ($parameters as $parameter => $value) {
            if (isset($handlers[$parameter])) {
                $parameters[$parameter] = $handlers[$parameter]($value);
            }
        }
        return $parameters;
    }

    private function tryInitializeValue($parameter) {
        if (isset(self::$_sharedOptions[$parameter])) {
            $value = self::$_sharedOptions[$parameter]->getDefault();
            $this->_parameters[$parameter] = $value;
            return $value;
        }
    }

    public function __get($parameter) {
        if (isset($this->_parameters[$parameter])) {
            return $this->_parameters[$parameter];
        }
        return $this->tryInitializeValue($parameter);
    }

    public function __isset($parameter) {
        if (isset($this->_parameters[$parameter])) {
            return true;
        }
        $value = $this->tryInitializeValue($parameter);
        return isset($value);
    }
}

class ClientOptions {
    private $_handlers, $_options;
    private static $_sharedOptions;

    public function __construct($options = null) {
        $this->initialize($options ?: array());
    }

    private static function getSharedOptions() {
        if (isset(self::$_sharedOptions)) {
            return self::$_sharedOptions;
        }
        self::$_sharedOptions = array(
            'profile' => new Options\ClientProfile(),
            'key_distribution' => new Options\ClientKeyDistribution(),
            'iterable_multibulk' => new Options\ClientIterableMultiBulk(),
            'throw_on_error' => new Options\ClientThrowOnError(),
            'on_connection_initialized' => new Options\CustomOption(array(
                'validate' => function($value) {
                    if (isset($value) && is_callable($value)) {
                        return $value;
                    }
                },
            )),
        );
        return self::$_sharedOptions;
    }

    private function initialize($options) {
        $this->_handlers = $this->getOptions();
        foreach ($options as $option => $value) {
            if (isset($this->_handlers[$option])) {
                $handler = $this->_handlers[$option];
                $this->_options[$option] = $handler($value);
            }
        }
    }

    private function getOptions() {
        return self::getSharedOptions();
    }

    protected function defineOption($name, Options\IOption $option) {
        $this->_handlers[$name] = $option;
    }

    public function __get($option) {
        if (!isset($this->_options[$option])) {
            $handler = $this->_handlers[$option];
            $this->_options[$option] = $handler->getDefault();
        }
        return $this->_options[$option];
    }

    public function __isset($option) {
        return isset(self::$_sharedOptions[$option]);
    }
}

class Utils {
    public static function isCluster(IConnection $connection) {
        return $connection instanceof IConnectionCluster;
    }

    public static function onCommunicationException(CommunicationException $exception) {
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

/* -------------------------------------------------------------------------- */

namespace Predis\Options;

use Predis\Profiles\ServerProfile;
use Predis\Profiles\IServerProfile;

interface IOption {
    public function validate($value);
    public function getDefault();
    public function __invoke($value);
}

class Option implements IOption {
    public function validate($value) {
        return $value;
    }

    public function getDefault() {
        return null;
    }

    public function __invoke($value) {
        return $this->validate($value ?: $this->getDefault());
    }
}

class CustomOption extends Option {
    private $__validate, $_default;

    public function __construct(Array $options) {
        $validate = isset($options['validate']) ? $options['validate'] : 'parent::validate';
        $default  = isset($options['default']) ? $options['default'] : 'parent::getDefault';
        if (!is_callable($validate) || !is_callable($default)) {
            throw new \InvalidArgumentException("Validate and default must be callable");
        }
        $this->_validate = $validate;
        $this->_default  = $default;
    }

    public function validate($value) {
        return call_user_func($this->_validate, $value);
    }

    public function getDefault() {
        return $this->validate(call_user_func($this->_default));
    }
}

class ClientProfile extends Option {
    public function validate($value) {
        if ($value instanceof IServerProfile) {
            return $value;
        }
        if (is_string($value)) {
            return ServerProfile::get($value);
        }
        throw new \InvalidArgumentException(
            "Invalid value for the profile option"
        );
    }

    public function getDefault() {
        return ServerProfile::getDefault();
    }
}

class ClientKeyDistribution extends Option {
    public function validate($value) {
        if ($value instanceof \Predis\Distribution\IDistributionStrategy) {
            return $value;
        }
        if (is_string($value)) {
            $valueReflection = new \ReflectionClass($value);
            if ($valueReflection->isSubclassOf('\Predis\Distribution\IDistributionStrategy')) {
                return new $value;
            }
        }
        throw new \InvalidArgumentException("Invalid value for key distribution");
    }

    public function getDefault() {
        return new \Predis\Distribution\HashRing();
    }
}

class ClientIterableMultiBulk extends Option {
    public function validate($value) {
        return (bool) $value;
    }

    public function getDefault() {
        return false;
    }
}

class ClientThrowOnError extends Option {
    public function validate($value) {
        return (bool) $value;
    }

    public function getDefault() {
        return true;
    }
}

/* -------------------------------------------------------------------------- */

namespace Predis\Network;

use Predis\Utils;
use Predis\ICommand;
use Predis\ConnectionParameters;
use Predis\CommunicationException;
use Predis\Protocols\IRedisProtocol;
use Predis\Protocols\TextProtocol;
use Predis\Distribution\IDistributionStrategy;

interface IConnection {
    public function connect();
    public function disconnect();
    public function isConnected();
    public function writeCommand(ICommand $command);
    public function readResponse(ICommand $command);
    public function executeCommand(ICommand $command);
}

interface IConnectionSingle extends IConnection {
    public function getParameters();
    public function getProtocol();
    public function setProtocol(IRedisProtocol $protocol);
    public function __toString();
    public function writeBytes($buffer);
    public function readBytes($length);
    public function readLine();
    public function pushInitCommand(ICommand $command);
}

interface IConnectionCluster extends IConnection {
    public function add(IConnectionSingle $connection);
    public function getConnection(ICommand $command);
    public function getConnectionById($connectionId);
}

abstract class ConnectionBase implements IConnectionSingle {
    private $_cachedId;
    protected $_params, $_socket, $_initCmds, $_protocol;

    public function __construct(ConnectionParameters $parameters, IRedisProtocol $protocol) {
        $this->_initCmds = array();
        $this->_params   = $parameters;
        $this->_protocol = $protocol;
    }

    public function __destruct() {
        $this->disconnect();
    }

    public function isConnected() {
        return is_resource($this->_socket);
    }

    protected abstract function createResource();

    public function connect() {
        if ($this->isConnected()) {
            throw new ClientException('Connection already estabilished');
        }
        $this->createResource();
    }

    public function disconnect() {
        if ($this->isConnected()) {
            fclose($this->_socket);
        }
    }

    public function pushInitCommand(ICommand $command){
        $this->_initCmds[] = $command;
    }

    public function executeCommand(ICommand $command) {
        $this->writeCommand($command);
        if ($command->closesConnection()) {
            return $this->disconnect();
        }
        return $this->readResponse($command);
    }

    protected function onCommunicationException($message, $code = null) {
        Utils::onCommunicationException(
            new CommunicationException($this, $message, $code)
        );
    }

    public function getResource() {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->_socket;
    }

    public function getParameters() {
        return $this->_params;
    }

    public function getProtocol() {
        return $this->_protocol;
    }

    public function setProtocol(IRedisProtocol $protocol) {
        $this->_protocol = $protocol;
    }

    public function __toString() {
        if (!isset($this->_cachedId)) {
            $this->_cachedId = "{$this->_params->host}:{$this->_params->port}";
        }
        return $this->_cachedId;
    }
}

class TcpConnection extends ConnectionBase implements IConnectionSingle {
    public function __construct(ConnectionParameters $parameters, IRedisProtocol $protocol = null) {
        parent::__construct($this->checkParameters($parameters), $protocol ?: new TextProtocol());
    }

    public function __destruct() {
        if (!$this->_params->connection_persistent) {
            $this->disconnect();
        }
    }

    protected function checkParameters(ConnectionParameters $parameters) {
        $scheme = $parameters->scheme;
        if ($scheme != 'tcp' && $scheme != 'redis') {
            throw new \InvalidArgumentException("Invalid scheme: {$scheme}");
        }
        return $parameters;
    }

    protected function createResource() {
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
    }

    private function sendInitializationCommands() {
        foreach ($this->_initCmds as $command) {
            $this->writeCommand($command);
        }
        foreach ($this->_initCmds as $command) {
            $this->readResponse($command);
        }
    }

    public function connect() {
        parent::connect();
        if (count($this->_initCmds) > 0){
            $this->sendInitializationCommands();
        }
    }

    public function writeCommand(ICommand $command) {
        $this->_protocol->write($this, $command);
    }

    public function readResponse(ICommand $command) {
        $response = $this->_protocol->read($this);
        $skipparse = isset($response->queued) || isset($response->error);
        return $skipparse ? $response : $command->parseResponse($response);
    }

    public function writeBytes($value) {
        $socket = $this->getResource();
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
        $socket = $this->getResource();
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
        $socket = $this->getResource();
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
}

class UnixDomainSocketConnection extends TcpConnection {
    protected function checkParameters(ConnectionParameters $parameters) {
        if ($parameters->scheme != 'unix') {
            throw new \InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
        $pathToSocket = $parameters->path;
        if (!isset($pathToSocket)) {
            throw new \InvalidArgumentException('Missing UNIX domain socket path');
        }
        if (!file_exists($pathToSocket)) {
            throw new \InvalidArgumentException("Could not find $pathToSocket");
        }
        return $parameters;
    }

    protected function createResource() {
        $uri = sprintf('unix:///%s', $this->_params->path);
        $connectFlags = STREAM_CLIENT_CONNECT;
        if ($this->_params->connection_persistent) {
            $connectFlags |= STREAM_CLIENT_PERSISTENT;
        }
        $this->_socket = @stream_socket_client(
            $uri, $errno, $errstr, $this->_params->connection_timeout, $connectFlags
        );
        if (!$this->_socket) {
            $this->onCommunicationException(trim($errstr), $errno);
        }
    }
}

class ConnectionCluster implements IConnectionCluster, \IteratorAggregate {
    private $_pool, $_distributor;

    public function __construct(IDistributionStrategy $distributor = null) {
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

    public function add(IConnectionSingle $connection) {
        $parameters = $connection->getParameters();
        if (isset($parameters->alias)) {
            $this->_pool[$parameters->alias] = $connection;
        }
        else {
            $this->_pool[] = $connection;
        }
        $this->_distributor->add($connection, $parameters->weight);
    }

    public function getConnection(ICommand $command) {
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

    public function writeCommand(ICommand $command) {
        $this->getConnection($command)->writeCommand($command);
    }

    public function readResponse(ICommand $command) {
        return $this->getConnection($command)->readResponse($command);
    }

    public function executeCommand(ICommand $command) {
        $connection = $this->getConnection($command);
        $connection->writeCommand($command);
        return $connection->readResponse($command);
    }
}

/* -------------------------------------------------------------------------- */

namespace Predis\Protocols;

use Predis\ICommand;
use Predis\Iterators;
use Predis\MalformedServerResponse;
use Predis\Network\IConnectionSingle;

interface IRedisProtocol {
    public function write(IConnectionSingle $connection, ICommand $command);
    public function read(IConnectionSingle $connection);
    public function setOption($option, $value);
}

interface IRedisProtocolExtended extends IRedisProtocol {
    public function setSerializer(ICommandSerializer $serializer);
    public function getSerializer();
    public function setReader(IResponseReader $reader);
    public function getReader();
}

interface ICommandSerializer {
    public function serialize(ICommand $command);
}

interface IResponseReader {
    public function setHandler($prefix, IResponseHandler $handler);
    public function getHandler($prefix);
}

interface IResponseHandler {
    function handle(IConnectionSingle $connection, $payload);
}

class TextProtocol implements IRedisProtocolExtended {
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

    private $_serializer, $_reader;

    public function __construct(Array $options = array()) {
        $this->setSerializer(new TextCommandSerializer());
        $this->setReader(new TextResponseReader());
        if (count($options) > 0) {
            $this->initializeOptions($options);
        }
    }

    private function initializeOptions(Array $options) {
        foreach ($options as $k => $v) {
            $this->setOption($k, $v);
        }
    }

    public function setOption($option, $value) {
        switch ($option) {
            case 'iterable_multibulk':
                $handler = $value ? new ResponseMultiBulkStreamHandler() : new ResponseMultiBulkHandler();
                $this->_reader->setHandler(self::PREFIX_MULTI_BULK, $handler);
                break;
            case 'throw_on_error':
                $handler = $value ? new ResponseErrorHandler() : new ResponseErrorSilentHandler();
                $this->_reader->setHandler(self::PREFIX_ERROR, $handler);
                break;
            default:
                throw new \InvalidArgumentException(
                    "The option $option is not supported by the current protocol"
                );
        }
    }

    public function serialize(ICommand $command) {
        return $this->_serializer->serialize($command);
    }

    public function write(IConnectionSingle $connection, ICommand $command) {
        $connection->writeBytes($this->_serializer->serialize($command));
    }

    public function read(IConnectionSingle $connection) {
        return $this->_reader->read($connection);
    }

    public function setSerializer(ICommandSerializer $serializer) {
        $this->_serializer = $serializer;
    }

    public function getSerializer() {
        return $this->_serializer;
    }

    public function setReader(IResponseReader $reader) {
        $this->_reader = $reader;
    }

    public function getReader() {
        return $this->_reader;
    }
}

class TextCommandSerializer implements ICommandSerializer {
    public function serialize(ICommand $command) {
        $commandId = $command->getCommandId();
        $arguments = $command->getArguments();

        $cmdlen  = strlen($commandId);
        $reqlen  = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandId}\r\n";
        for ($i = 0; $i < $reqlen - 1; $i++) {
            $argument = $arguments[$i];
            $arglen  = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        return $buffer;
    }
}

class TextResponseReader implements IResponseReader {
    private $_prefixHandlers;

    public function __construct() {
        $this->_prefixHandlers = $this->getDefaultHandlers();
    }

    private function getDefaultHandlers() {
        return array(
            TextProtocol::PREFIX_STATUS     => new ResponseStatusHandler(),
            TextProtocol::PREFIX_ERROR      => new ResponseErrorHandler(),
            TextProtocol::PREFIX_INTEGER    => new ResponseIntegerHandler(),
            TextProtocol::PREFIX_BULK       => new ResponseBulkHandler(),
            TextProtocol::PREFIX_MULTI_BULK => new ResponseMultiBulkHandler(),
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

    public function read(IConnectionSingle $connection) {
        $header = $connection->readLine();
        if ($header === '') {
            $this->throwMalformedResponse('Unexpected empty header');
        }

        $prefix = $header[0];
        if (!isset($this->_prefixHandlers[$prefix])) {
            $this->throwMalformedResponse("Unknown prefix '$prefix'");
        }
        $handler = $this->_prefixHandlers[$prefix];
        return $handler->handle($connection, substr($header, 1));
    }

    private function throwMalformedResponse($message) {
        Utils::onCommunicationException(new MalformedServerResponse(
            $connection, $message
        ));
    }
}

class ResponseStatusHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $status) {
        if ($status === 'OK') {
            return true;
        }
        if ($status === 'QUEUED') {
            return new \Predis\ResponseQueued();
        }
        return $status;
    }
}

class ResponseErrorHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $errorMessage) {
        throw new \Predis\ServerException(substr($errorMessage, 4));
    }
}

class ResponseErrorSilentHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $errorMessage) {
        return new \Predis\ResponseError(substr($errorMessage, 4));
    }
}

class ResponseBulkHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $length) {
        if (!is_numeric($length)) {
            Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$length' as data length"
            ));
        }

        $length = (int) $length;
        if ($length >= 0) {
          return $length > 0 ? substr($connection->readBytes($length + 2), 0, -2) : '';
        }
        if ($length == -1) {
            return null;
        }
    }
}

class ResponseMultiBulkHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $length) {
        if (!is_numeric($length)) {
            Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$length' as data length"
            ));
        }

        $length = (int) $length;
        if ($length === -1) {
            return null;
        }

        $list = array();
        if ($length > 0) {
            $handlersCache = array();
            $reader = $connection->getProtocol()->getReader();
            for ($i = 0; $i < $length; $i++) {
                $header = $connection->readLine();
                $prefix = $header[0];
                if (isset($handlersCache[$prefix])) {
                    $handler = $handlersCache[$prefix];
                }
                else {
                    $handler = $reader->getHandler($prefix);
                    $handlersCache[$prefix] = $handler;
                }
                $list[$i] = $handler->handle($connection, substr($header, 1));
            }
        }
        return $list;
    }
}

class ResponseMultiBulkStreamHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $length) {
        if (!is_numeric($length)) {
            Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$length' as data length"
            ));
        }
        return new Iterators\MultiBulkResponseSimple($connection, (int) $length);
    }
}

class ResponseIntegerHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $number) {
        if (is_numeric($number)) {
            return (int) $number;
        }
        if ($number !== 'nil') {
            Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$number' as numeric response"
            ));
        }
        return null;
    }
}

/* -------------------------------------------------------------------------- */

namespace Predis\Profiles;

use Predis\ClientException;

interface IServerProfile {
    public function getVersion();
    public function supportsCommand($command);
    public function supportsCommands(Array $commands);
    public function registerCommand($command, $aliases);
    public function registerCommands(Array $commands);
    public function createCommand($method, $arguments = array());
}

abstract class ServerProfile implements IServerProfile {
    private static $_profiles;
    private $_registeredCommands;

    public function __construct() {
        $this->_registeredCommands = $this->getSupportedCommands();
    }

    protected abstract function getSupportedCommands();

    public static function getDefault() {
        return self::get('default');
    }

    public static function getDevelopment() {
        return self::get('dev');
    }

    private static function getDefaultProfiles() {
        return array(
            '1.2'     => '\Predis\Profiles\Server_v1_2',
            '2.0'     => '\Predis\Profiles\Server_v2_0',
            'default' => '\Predis\Profiles\Server_v2_0',
            'dev'     => '\Predis\Profiles\Server_vNext',
        );
    }

    public static function registerProfile($profileClass, $aliases) {
        if (!isset(self::$_profiles)) {
            self::$_profiles = self::getDefaultProfiles();
        }

        $profileReflection = new \ReflectionClass($profileClass);
        if (!$profileReflection->isSubclassOf('\Predis\Profiles\IServerProfile')) {
            throw new ClientException(
                "Cannot register '$profileClass' as it is not a valid profile class"
            );
        }

        if (is_array($aliases)) {
            foreach ($aliases as $alias) {
                self::$_profiles[$alias] = $profileClass;
            }
        }
        else {
            self::$_profiles[$aliases] = $profileClass;
        }
    }

    public static function get($version) {
        if (!isset(self::$_profiles)) {
            self::$_profiles = self::getDefaultProfiles();
        }
        if (!isset(self::$_profiles[$version])) {
            throw new ClientException("Unknown server profile: $version");
        }
        $profile = self::$_profiles[$version];
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

        if (!$commandReflection->isSubclassOf('\Predis\ICommand')) {
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

class Server_v1_2 extends ServerProfile {
    public function getVersion() { return '1.2'; }
    public function getSupportedCommands() {
        return array(
            /* miscellaneous commands */
            'ping'                      => '\Predis\Commands\Ping',
            'echo'                      => '\Predis\Commands\DoEcho',
            'auth'                      => '\Predis\Commands\Auth',

            /* connection handling */
            'quit'                      => '\Predis\Commands\Quit',

            /* commands operating on string values */
            'set'                       => '\Predis\Commands\Set',
            'setnx'                     => '\Predis\Commands\SetPreserve',
            'mset'                      => '\Predis\Commands\SetMultiple',
            'msetnx'                    => '\Predis\Commands\SetMultiplePreserve',
            'get'                       => '\Predis\Commands\Get',
            'mget'                      => '\Predis\Commands\GetMultiple',
            'getset'                    => '\Predis\Commands\GetSet',
            'incr'                      => '\Predis\Commands\Increment',
            'incrby'                    => '\Predis\Commands\IncrementBy',
            'decr'                      => '\Predis\Commands\Decrement',
            'decrby'                    => '\Predis\Commands\DecrementBy',
            'exists'                    => '\Predis\Commands\Exists',
            'del'                       => '\Predis\Commands\Delete',
            'type'                      => '\Predis\Commands\Type',

            /* commands operating on the key space */
            'keys'                      => '\Predis\Commands\Keys_v1_2',
            'randomkey'                 => '\Predis\Commands\RandomKey',
            'rename'                    => '\Predis\Commands\Rename',
            'renamenx'                  => '\Predis\Commands\RenamePreserve',
            'expire'                    => '\Predis\Commands\Expire',
            'expireat'                  => '\Predis\Commands\ExpireAt',
            'dbsize'                    => '\Predis\Commands\DatabaseSize',
            'ttl'                       => '\Predis\Commands\TimeToLive',

            /* commands operating on lists */
            'rpush'                     => '\Predis\Commands\ListPushTail',
            'lpush'                     => '\Predis\Commands\ListPushHead',
            'llen'                      => '\Predis\Commands\ListLength',
            'lrange'                    => '\Predis\Commands\ListRange',
            'ltrim'                     => '\Predis\Commands\ListTrim',
            'lindex'                    => '\Predis\Commands\ListIndex',
            'lset'                      => '\Predis\Commands\ListSet',
            'lrem'                      => '\Predis\Commands\ListRemove',
            'lpop'                      => '\Predis\Commands\ListPopFirst',
            'rpop'                      => '\Predis\Commands\ListPopLast',
            'rpoplpush'                 => '\Predis\Commands\ListPopLastPushHead',

            /* commands operating on sets */
            'sadd'                      => '\Predis\Commands\SetAdd',
            'srem'                      => '\Predis\Commands\SetRemove',
            'spop'                      => '\Predis\Commands\SetPop',
            'smove'                     => '\Predis\Commands\SetMove',
            'scard'                     => '\Predis\Commands\SetCardinality',
            'sismember'                 => '\Predis\Commands\SetIsMember',
            'sinter'                    => '\Predis\Commands\SetIntersection',
            'sinterstore'               => '\Predis\Commands\SetIntersectionStore',
            'sunion'                    => '\Predis\Commands\SetUnion',
            'sunionstore'               => '\Predis\Commands\SetUnionStore',
            'sdiff'                     => '\Predis\Commands\SetDifference',
            'sdiffstore'                => '\Predis\Commands\SetDifferenceStore',
            'smembers'                  => '\Predis\Commands\SetMembers',
            'srandmember'               => '\Predis\Commands\SetRandomMember',

            /* commands operating on sorted sets */
            'zadd'                      => '\Predis\Commands\ZSetAdd',
            'zincrby'                   => '\Predis\Commands\ZSetIncrementBy',
            'zrem'                      => '\Predis\Commands\ZSetRemove',
            'zrange'                    => '\Predis\Commands\ZSetRange',
            'zrevrange'                 => '\Predis\Commands\ZSetReverseRange',
            'zrangebyscore'             => '\Predis\Commands\ZSetRangeByScore',
            'zcard'                     => '\Predis\Commands\ZSetCardinality',
            'zscore'                    => '\Predis\Commands\ZSetScore',
            'zremrangebyscore'          => '\Predis\Commands\ZSetRemoveRangeByScore',

            /* multiple databases handling commands */
            'select'                    => '\Predis\Commands\SelectDatabase',
            'move'                      => '\Predis\Commands\MoveKey',
            'flushdb'                   => '\Predis\Commands\FlushDatabase',
            'flushall'                  => '\Predis\Commands\FlushAll',

            /* sorting */
            'sort'                      => '\Predis\Commands\Sort',

            /* remote server control commands */
            'info'                      => '\Predis\Commands\Info',
            'slaveof'                   => '\Predis\Commands\SlaveOf',

            /* persistence control commands */
            'save'                      => '\Predis\Commands\Save',
            'bgsave'                    => '\Predis\Commands\BackgroundSave',
            'lastsave'                  => '\Predis\Commands\LastSave',
            'shutdown'                  => '\Predis\Commands\Shutdown',
            'bgrewriteaof'              => '\Predis\Commands\BackgroundRewriteAppendOnlyFile',
        );
    }
}

class Server_v2_0 extends Server_v1_2 {
    public function getVersion() { return '2.0'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* transactions */
            'multi'                     => '\Predis\Commands\Multi',
            'exec'                      => '\Predis\Commands\Exec',
            'discard'                   => '\Predis\Commands\Discard',

            /* commands operating on string values */
            'setex'                     => '\Predis\Commands\SetExpire',
            'append'                    => '\Predis\Commands\Append',
            'substr'                    => '\Predis\Commands\Substr',

            /* commands operating on the key space */
            'keys'                      => '\Predis\Commands\Keys',

            /* commands operating on lists */
            'blpop'                     => '\Predis\Commands\ListPopFirstBlocking',
            'brpop'                     => '\Predis\Commands\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'zunionstore'               => '\Predis\Commands\ZSetUnionStore',
            'zinterstore'               => '\Predis\Commands\ZSetIntersectionStore',
            'zcount'                    => '\Predis\Commands\ZSetCount',
            'zrank'                     => '\Predis\Commands\ZSetRank',
            'zrevrank'                  => '\Predis\Commands\ZSetReverseRank',
            'zremrangebyrank'           => '\Predis\Commands\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'hset'                      => '\Predis\Commands\HashSet',
            'hsetnx'                    => '\Predis\Commands\HashSetPreserve',
            'hmset'                     => '\Predis\Commands\HashSetMultiple',
            'hincrby'                   => '\Predis\Commands\HashIncrementBy',
            'hget'                      => '\Predis\Commands\HashGet',
            'hmget'                     => '\Predis\Commands\HashGetMultiple',
            'hdel'                      => '\Predis\Commands\HashDelete',
            'hexists'                   => '\Predis\Commands\HashExists',
            'hlen'                      => '\Predis\Commands\HashLength',
            'hkeys'                     => '\Predis\Commands\HashKeys',
            'hvals'                     => '\Predis\Commands\HashValues',
            'hgetall'                   => '\Predis\Commands\HashGetAll',

            /* publish - subscribe */
            'subscribe'                 => '\Predis\Commands\Subscribe',
            'unsubscribe'               => '\Predis\Commands\Unsubscribe',
            'psubscribe'                => '\Predis\Commands\SubscribeByPattern',
            'punsubscribe'              => '\Predis\Commands\UnsubscribeByPattern',
            'publish'                   => '\Predis\Commands\Publish',

            /* remote server control commands */
            'config'                    => '\Predis\Commands\Config',
        ));
    }
}

class Server_vNext extends Server_v2_0 {
    public function getVersion() { return '2.1'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* transactions */
            'watch'                     => '\Predis\Commands\Watch',
            'unwatch'                   => '\Predis\Commands\Unwatch',

            /* commands operating on string values */
            'strlen'                    => '\Predis\Commands\Strlen',
            'setrange'                  => '\Predis\Commands\SetRange',
            'getrange'                  => '\Predis\Commands\GetRange',
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

/* -------------------------------------------------------------------------- */

namespace Predis\Distribution;

interface IDistributionStrategy {
    public function add($node, $weight = null);
    public function remove($node);
    public function get($key);
    public function generateKey($value);
}

class EmptyRingException extends \Exception {
}

class HashRing implements IDistributionStrategy {
    const DEFAULT_REPLICAS = 128;
    const DEFAULT_WEIGHT   = 100;
    private $_nodes, $_ring, $_ringKeys, $_ringKeysCount, $_replicas;

    public function __construct($replicas = self::DEFAULT_REPLICAS) {
        $this->_replicas = $replicas;
        $this->_nodes    = array();
    }

    public function add($node, $weight = null) {
        // In case of collisions in the hashes of the nodes, the node added
        // last wins, thus the order in which nodes are added is significant.
        $this->_nodes[] = array('object' => $node, 'weight' => (int) $weight ?: $this::DEFAULT_WEIGHT);
        $this->reset();
    }

    public function remove($node) {
        // A node is removed by resetting the ring so that it's recreated from
        // scratch, in order to reassign possible hashes with collisions to the
        // right node according to the order in which they were added in the
        // first place.
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
        // Binary search for the last item in _ringkeys with a value less or
        // equal to the key. If no such item exists, return the last item.
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
        // Binary search for the first item in _ringkeys with a value greater
        // or equal to the key. If no such item exists, return the first item.
        return $lower < $ringKeysCount ? $lower : 0;
    }
}

/* -------------------------------------------------------------------------- */

namespace Predis\Pipeline;

use Predis\Network\IConnection;

interface IPipelineExecutor {
    public function execute(IConnection $connection, &$commands);
}

class StandardExecutor implements IPipelineExecutor {
    public function execute(IConnection $connection, &$commands) {
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
        catch (\Predis\ServerException $exception) {
            // Force disconnection to prevent protocol desynchronization.
            $connection->disconnect();
            throw $exception;
        }

        return $values;
    }
}

class SafeExecutor implements IPipelineExecutor {
    public function execute(IConnection $connection, &$commands) {
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
    public function execute(IConnection $connection, &$commands) {
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

/* -------------------------------------------------------------------------- */

namespace Predis\Iterators;

use Predis\CommunicationException;
use Predis\Network\IConnection;
use Predis\Network\IConnectionSingle;

abstract class MultiBulkResponse implements \Iterator, \Countable {
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
        // Use count if you want to get the size of the current multi-bulk
        // response without using iterator_count (which actually consumes our
        // iterator to calculate the size, and we cannot perform a rewind)
        return $this->_replySize;
    }

    protected abstract function getValue();
}

class MultiBulkResponseSimple extends MultiBulkResponse {
    private $_connection;

    public function __construct(IConnectionSingle $connection, $size) {
        $this->_connection = $connection;
        $this->_protocol   = $connection->getProtocol();
        $this->_position   = 0;
        $this->_current    = $size > 0 ? $this->getValue() : null;
        $this->_replySize  = $size;
    }

    public function __destruct() {
        // When the iterator is garbage-collected (e.g. it goes out of the
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
        return $this->_protocol->read($this->_connection);
    }
}

class MultiBulkResponseTuple extends MultiBulkResponse {
    private $_iterator;

    public function __construct(MultiBulkResponseSimple $iterator) {
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

/* -------------------------------------------------------------------------- */

namespace Predis\Commands;

use Predis\Utils;
use Predis\Command;
use Predis\Iterators\MultiBulkResponseTuple;

/* miscellaneous commands */
class Ping extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}

class DoEcho extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}

class Auth extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}

/* connection handling */
class Quit extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}

/* commands operating on string values */
class Set extends Command {
    public function getCommandId() { return 'SET'; }
}

class SetExpire extends Command {
    public function getCommandId() { return 'SETEX'; }
}

class SetPreserve extends Command {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetMultiple extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSET'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 1 && is_array($arguments[0])) {
            $flattenedKVs = array();
            $args = $arguments[0];
            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }
}

class SetMultiplePreserve extends SetMultiple {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Get extends Command {
    public function getCommandId() { return 'GET'; }
}

class GetMultiple extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}

class GetSet extends Command {
    public function getCommandId() { return 'GETSET'; }
}

class Increment extends Command {
    public function getCommandId() { return 'INCR'; }
}

class IncrementBy extends Command {
    public function getCommandId() { return 'INCRBY'; }
}

class Decrement extends Command {
    public function getCommandId() { return 'DECR'; }
}

class DecrementBy extends Command {
    public function getCommandId() { return 'DECRBY'; }
}

class Exists extends Command {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Delete extends Command {
    public function getCommandId() { return 'DEL'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}

class Type extends Command {
    public function getCommandId() { return 'TYPE'; }
}

class Append extends Command {
    public function getCommandId() { return 'APPEND'; }
}

class SetRange extends Command {
    public function getCommandId() { return 'SETRANGE'; }
}

class GetRange extends Command {
    public function getCommandId() { return 'GETRANGE'; }
}

class Substr extends Command {
    public function getCommandId() { return 'SUBSTR'; }
}

class SetBit extends Command {
    public function getCommandId() { return 'SETBIT'; }
}

class GetBit extends Command {
    public function getCommandId() { return 'GETBIT'; }
}

class Strlen extends Command {
    public function getCommandId() { return 'STRLEN'; }
}

/* commands operating on the key space */
class Keys extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
}

class Keys_v1_2 extends Keys {
    public function parseResponse($data) {
        return explode(' ', $data);
    }
}

class RandomKey extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}

class Rename extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}

class RenamePreserve extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Expire extends Command {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ExpireAt extends Command {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Persist extends Command {
    public function getCommandId() { return 'PERSIST'; }
    public function parseResponse($data) { return (bool) $data; }
}

class DatabaseSize extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}

class TimeToLive extends Command {
    public function getCommandId() { return 'TTL'; }
}

/* commands operating on lists */
class ListPushTail extends Command {
    public function getCommandId() { return 'RPUSH'; }
}

class ListPushTailX extends Command {
    public function getCommandId() { return 'RPUSHX'; }
}

class ListPushHead extends Command {
    public function getCommandId() { return 'LPUSH'; }
}

class ListPushHeadX extends Command {
    public function getCommandId() { return 'LPUSHX'; }
}

class ListLength extends Command {
    public function getCommandId() { return 'LLEN'; }
}

class ListRange extends Command {
    public function getCommandId() { return 'LRANGE'; }
}

class ListTrim extends Command {
    public function getCommandId() { return 'LTRIM'; }
}

class ListIndex extends Command {
    public function getCommandId() { return 'LINDEX'; }
}

class ListSet extends Command {
    public function getCommandId() { return 'LSET'; }
}

class ListRemove extends Command {
    public function getCommandId() { return 'LREM'; }
}

class ListPopLastPushHead extends Command {
    public function getCommandId() { return 'RPOPLPUSH'; }
}

class ListPopLastPushHeadBlocking extends Command {
    public function getCommandId() { return 'BRPOPLPUSH'; }
}

class ListPopFirst extends Command {
    public function getCommandId() { return 'LPOP'; }
}

class ListPopLast extends Command {
    public function getCommandId() { return 'RPOP'; }
}

class ListPopFirstBlocking extends Command {
    public function getCommandId() { return 'BLPOP'; }
}

class ListPopLastBlocking extends Command {
    public function getCommandId() { return 'BRPOP'; }
}

class ListInsert extends Command {
    public function getCommandId() { return 'LINSERT'; }
}

/* commands operating on sets */
class SetAdd extends Command {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetRemove extends Command {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetPop  extends Command {
    public function getCommandId() { return 'SPOP'; }
}

class SetMove extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetCardinality extends Command {
    public function getCommandId() { return 'SCARD'; }
}

class SetIsMember extends Command {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetIntersection extends Command {
    public function getCommandId() { return 'SINTER'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}

class SetIntersectionStore extends Command {
    public function getCommandId() { return 'SINTERSTORE'; }
    public function filterArguments(Array $arguments) {
        return Utils::filterArrayArguments($arguments);
    }
}

class SetUnion extends SetIntersection {
    public function getCommandId() { return 'SUNION'; }
}

class SetUnionStore extends SetIntersectionStore {
    public function getCommandId() { return 'SUNIONSTORE'; }
}

class SetDifference extends SetIntersection {
    public function getCommandId() { return 'SDIFF'; }
}

class SetDifferenceStore extends SetIntersectionStore {
    public function getCommandId() { return 'SDIFFSTORE'; }
}

class SetMembers extends Command {
    public function getCommandId() { return 'SMEMBERS'; }
}

class SetRandomMember extends Command {
    public function getCommandId() { return 'SRANDMEMBER'; }
}

/* commands operating on sorted sets */
class ZSetAdd extends Command {
    public function getCommandId() { return 'ZADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ZSetIncrementBy extends Command {
    public function getCommandId() { return 'ZINCRBY'; }
}

class ZSetRemove extends Command {
    public function getCommandId() { return 'ZREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ZSetUnionStore extends Command {
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

class ZSetIntersectionStore extends ZSetUnionStore {
    public function getCommandId() { return 'ZINTERSTORE'; }
}

class ZSetRange extends Command {
    private $_withScores = false;
    public function getCommandId() { return 'ZRANGE'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 4) {
            $lastType = gettype($arguments[3]);
            if ($lastType === 'string' && strtolower($arguments[3]) === 'withscores') {
                // Used for compatibility with older versions
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
                return new MultiBulkResponseTuple($data);
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

class ZSetReverseRange extends ZSetRange {
    public function getCommandId() { return 'ZREVRANGE'; }
}

class ZSetRangeByScore extends ZSetRange {
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

class ZSetReverseRangeByScore extends ZSetRangeByScore {
    public function getCommandId() { return 'ZREVRANGEBYSCORE'; }
}

class ZSetCount extends Command {
    public function getCommandId() { return 'ZCOUNT'; }
}

class ZSetCardinality extends Command {
    public function getCommandId() { return 'ZCARD'; }
}

class ZSetScore extends Command {
    public function getCommandId() { return 'ZSCORE'; }
}

class ZSetRemoveRangeByScore extends Command {
    public function getCommandId() { return 'ZREMRANGEBYSCORE'; }
}

class ZSetRank extends Command {
    public function getCommandId() { return 'ZRANK'; }
}

class ZSetReverseRank extends Command {
    public function getCommandId() { return 'ZREVRANK'; }
}

class ZSetRemoveRangeByRank extends Command {
    public function getCommandId() { return 'ZREMRANGEBYRANK'; }
}

/* commands operating on hashes */
class HashSet extends Command {
    public function getCommandId() { return 'HSET'; }
    public function parseResponse($data) { return (bool) $data; }
}

class HashSetPreserve extends Command {
    public function getCommandId() { return 'HSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class HashSetMultiple extends Command {
    public function getCommandId() { return 'HMSET'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $flattenedKVs = array($arguments[0]);
            $args = $arguments[1];
            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }
}

class HashIncrementBy extends Command {
    public function getCommandId() { return 'HINCRBY'; }
}

class HashGet extends Command {
    public function getCommandId() { return 'HGET'; }
}

class HashGetMultiple extends Command {
    public function getCommandId() { return 'HMGET'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $flattenedKVs = array($arguments[0]);
            $args = $arguments[1];
            foreach ($args as $v) {
                $flattenedKVs[] = $v;
            }
            return $flattenedKVs;
        }
        return $arguments;
    }
}

class HashDelete extends Command {
    public function getCommandId() { return 'HDEL'; }
    public function parseResponse($data) { return (bool) $data; }
}

class HashExists extends Command {
    public function getCommandId() { return 'HEXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class HashLength extends Command {
    public function getCommandId() { return 'HLEN'; }
}

class HashKeys extends Command {
    public function getCommandId() { return 'HKEYS'; }
}

class HashValues extends Command {
    public function getCommandId() { return 'HVALS'; }
}

class HashGetAll extends Command {
    public function getCommandId() { return 'HGETALL'; }
    public function parseResponse($data) {
        if ($data instanceof \Iterator) {
            return new MultiBulkResponseTuple($data);
        }
        $result = array();
        for ($i = 0; $i < count($data); $i++) {
            $result[$data[$i]] = $data[++$i];
        }
        return $result;
    }
}

/* multiple databases handling commands */
class SelectDatabase extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SELECT'; }
}

class MoveKey extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class FlushDatabase extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}

class FlushAll extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}

/* sorting */
class Sort extends Command {
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
class Multi extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MULTI'; }
}

class Exec extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'EXEC'; }
}

class Discard extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DISCARD'; }
}

class Watch extends Command {
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

class Unwatch extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNWATCH'; }
    public function parseResponse($data) { return (bool) $data; }
}

/* publish/subscribe */
class Subscribe extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SUBSCRIBE'; }
}

class Unsubscribe extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'UNSUBSCRIBE'; }
}

class SubscribeByPattern extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PSUBSCRIBE'; }
}

class UnsubscribeByPattern extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PUNSUBSCRIBE'; }
}

class Publish extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PUBLISH'; }
}

/* persistence control commands */
class Save extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}

class BackgroundSave extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGSAVE'; }
    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}

class BackgroundRewriteAppendOnlyFile extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGREWRITEAOF'; }
    public function parseResponse($data) {
        return $data == 'Background append only file rewriting started';
    }
}

class LastSave extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}

class Shutdown extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}

/* remote server control commands */
class Info extends Command {
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

class SlaveOf extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SLAVEOF'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }
        return $arguments;
    }
}

class Config extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'CONFIG'; }
}
?>
