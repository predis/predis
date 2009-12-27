<?php
namespace Predis;

class PredisException extends \Exception { }
class ClientException extends PredisException { }
class ServerException extends PredisException { }
class MalformedServerResponse extends ServerException { }

/* ------------------------------------------------------------------------- */

class Client {
    // TODO: command arguments should be sanitized or checked for bad arguments 
    //       (e.g. CRLF in keys for inline commands)

    private $_connection, $_serverProfile;

    public function __construct($parameters = null, RedisServerProfile $serverProfile = null) {
        $this->setServerProfile(
            $serverProfile === null 
                ? RedisServerProfile::getDefault() 
                : $serverProfile
        );
        $this->setupConnection($parameters);
    }

    public function __destruct() {
        $this->_connection->disconnect();
    }

    public static function create(/* arguments */) {
        $argv = func_get_args();
        $argc = func_num_args();

        $serverProfile = null;
        $lastArg = $argv[$argc-1];
        if ($argc > 0 && !is_string($lastArg) && is_subclass_of($lastArg, '\Predis\RedisServerProfile')) {
            $serverProfile = array_pop($argv);
            $argc--;
        }

        if ($argc === 0) {
            throw new ClientException('Missing connection parameters');
        }

        return new Client($argc === 1 ? $argv[0] : $argv, $serverProfile);
    }

    private function setupConnection($parameters) {
        if ($parameters !== null && !(is_array($parameters) || is_string($parameters))) {
            throw new ClientException('Invalid parameters type (array or string expected)');
        }

        if (is_array($parameters) && isset($parameters[0])) {
            $cluster = new ConnectionCluster();
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
        $params     = new ConnectionParameters($parameters);
        $connection = new Connection($params);

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

    public function setServerProfile(RedisServerProfile $serverProfile) {
        $this->_serverProfile = $serverProfile;
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

    public function getConnection() {
        return $this->_connection;
    }

    public function __call($method, $arguments) {
        $command = $this->_serverProfile->createCommand($method, $arguments);
        return $this->executeCommand($command);
    }

    public function createCommand($method, $arguments = array()) {
        return $this->_serverProfile->createCommand($method, $arguments);
    }

    private function executeCommandInternal(IConnection $connection, Command $command) {
        $connection->writeCommand($command);
        if ($command->closesConnection()) {
            return $connection->disconnect();
        }
        return $connection->readResponse($command);
    }

    public function executeCommand(Command $command) {
        return self::executeCommandInternal($this->_connection, $command);
    }

    public function executeCommandOnShards(Command $command) {
        $replies = array();
        if (is_a($this->_connection, '\Predis\ConnectionCluster')) {
            foreach($this->_connection as $connection) {
                $replies[] = self::executeCommandInternal($connection, $command);
            }
        }
        else {
            $replies[] = self::executeCommandInternal($this->_connection, $command);
        }
        return $replies;
    }

    public function rawCommand($rawCommandData, $closesConnection = false) {
        // TODO: rather than check the type of a connection instance, we should 
        //       check if it does respond to the rawCommand method.
        if (is_a($this->_connection, '\Predis\ConnectionCluster')) {
            throw new ClientException('Cannot send raw commands when connected to a cluster of Redis servers');
        }
        return $this->_connection->rawCommand($rawCommandData, $closesConnection);
    }

    public function pipeline(\Closure $pipelineBlock = null) {
        $pipeline = new CommandPipeline($this);
        return $pipelineBlock !== null ? $pipeline->execute($pipelineBlock) : $pipeline;
    }

    public function multiExec(\Closure $multiExecBlock = null) {
        $multiExec = new MultiExecBlock($this);
        return $multiExecBlock !== null ? $multiExec->execute($multiExecBlock) : $multiExec;
    }

    public function registerCommands(Array $commands) {
        $this->_serverProfile->registerCommands($commands);
    }

    public function registerCommand($command, $aliases) {
        $this->_serverProfile->registerCommand($command, $aliases);
    }
}

/* ------------------------------------------------------------------------- */

abstract class Command {
    private $_arguments, $_hash;

    public abstract function getCommandId();

    public abstract function serializeRequest($command, $arguments);

    public function canBeHashed() {
        return true;
    }

    public function getHash() {
        if (isset($this->_hash)) {
            return $this->_hash;
        }
        else {
            if (isset($this->_arguments[0])) {
                $key = $this->_arguments[0];

                $start = strpos($key, '{');
                $end   = strpos($key, '}');
                if ($start !== false && $end !== false) {
                    $key = substr($key, ++$start, $end - $start);
                }

                $this->_hash = crc32($key);
                return $this->_hash;
            }
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
    }

    public function setArgumentsArray(Array $arguments) {
        $this->_arguments = $this->filterArguments($arguments);
    }

    protected function getArguments() {
        return isset($this->_arguments) ? $this->_arguments : array();
    }

    public function getArgument($index = 0) {
        return isset($this->_arguments[$index]) ? $this->_arguments[$index] : null;
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
        return $command . ' ' . implode($arguments, ' ') . Response::NEWLINE;
    }
}

abstract class BulkCommand extends Command {
    public function serializeRequest($command, $arguments) {
        $data = array_pop($arguments);
        if (is_array($data)) {
            $data = implode($data, ' ');
        }
        return $command . ' ' . implode($arguments, ' ') . ' ' . strlen($data) . 
            Response::NEWLINE . $data . Response::NEWLINE;
    }
}

abstract class MultiBulkCommand extends Command {
    public function serializeRequest($command, $arguments) {
        $buffer   = array();
        $cmd_args = null;

        if (count($arguments) === 1 && is_array($arguments[0])) {
            $cmd_args = array();
            foreach ($arguments[0] as $k => $v) {
                $cmd_args[] = $k;
                $cmd_args[] = $v;
            }
        }
        else {
            $cmd_args = $arguments;
        }

        $buffer[] = '*' . ((string) count($cmd_args) + 1) . Response::NEWLINE;
        $buffer[] = '$' . strlen($command) . Response::NEWLINE . $command . Response::NEWLINE;
        foreach ($cmd_args as $argument) {
            $buffer[] = '$' . strlen($argument) . Response::NEWLINE . $argument . Response::NEWLINE;
        }

        return implode('', $buffer);
    }
}

/* ------------------------------------------------------------------------- */

class Response {
    const NEWLINE = "\r\n";
    const OK      = 'OK';
    const ERROR   = 'ERR';
    const NULL    = 'nil';

    private static $_prefixHandlers;

    private static function initializePrefixHandlers() {
        return array(
            // status
            '+' => function($socket) {
                $status = rtrim(fgets($socket), Response::NEWLINE);
                return $status === Response::OK ? true : $status;
            }, 

            // error
            '-' => function($socket) {
                $errorMessage = rtrim(fgets($socket), Response::NEWLINE);
                throw new ServerException(substr($errorMessage, 4));
            }, 

            // bulk
            '$' => function($socket) {
                $dataLength = rtrim(fgets($socket), Response::NEWLINE);

                if (!is_numeric($dataLength)) {
                    throw new ClientException("Cannot parse '$dataLength' as data length");
                }

                if ($dataLength > 0) {
                    $value = stream_get_contents($socket, $dataLength);
                    fread($socket, 2);
                    return $value;
                }
                else if ($dataLength == 0) {
                    // TODO: I just have a doubt here...
                    fread($socket, 2);
                }

                return null;
            }, 

            // multibulk
            '*' => function($socket) {
                $rawLength = rtrim(fgets($socket), Response::NEWLINE);
                if (!is_numeric($rawLength)) {
                    throw new ClientException("Cannot parse '$rawLength' as data length");
                }

                $listLength = (int) $rawLength;
                if ($listLength === -1) {
                    return null;
                }

                $list = array();

                if ($listLength > 0) {
                    for ($i = 0; $i < $listLength; $i++) {
                        $handler = Response::getPrefixHandler(fgetc($socket));
                        $list[] = $handler($socket);
                    }
                }

                return $list;
            }, 

            // integer
            ':' => function($socket) {
                $number = rtrim(fgets($socket), Response::NEWLINE);
                if (is_numeric($number)) {
                    return (int) $number;
                }
                else {
                    if ($number !== Response::NULL) {
                        throw new ClientException("Cannot parse '$number' as numeric response");
                    }
                    return null;
                }
            }
        );
    }

    public static function getPrefixHandler($prefix) {
        if (self::$_prefixHandlers === null) {
            self::$_prefixHandlers = self::initializePrefixHandlers();
        }

        $handler = self::$_prefixHandlers[$prefix];
        if ($handler === null) {
            throw new MalformedServerResponse("Unknown prefix '$prefix'");
        }
        return $handler;
    }
}

class CommandPipeline {
    private $_redisClient, $_pipelineBuffer, $_returnValues, $_running;

    public function __construct(Client $redisClient) {
        $this->_redisClient    = $redisClient;
        $this->_pipelineBuffer = array();
        $this->_returnValues   = array();
    }

    public function __call($method, $arguments) {
        $command = $this->_redisClient->createCommand($method, $arguments);
        $this->recordCommand($command);
    }

    private function recordCommand(Command $command) {
        $this->_pipelineBuffer[] = $command;
    }

    private function getRecordedCommands() {
        return $this->_pipelineBuffer;
    }

    public function flushPipeline() {
        if (count($this->_pipelineBuffer) === 0) {
            return;
        }

        $connection = $this->_redisClient->getConnection();
        $commands   = $this->getRecordedCommands();

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }
        foreach ($commands as $command) {
            $this->_returnValues[] = $connection->readResponse($command);
        }

        $this->_pipelineBuffer = array();
    }

    private function setRunning($bool) {
        // TODO: I am honest when I say that I don't like this approach.
        if ($bool == true && $this->_running == true) {
            throw new ClientException("This pipeline is already opened");
        }

        $this->_running = $bool;
    }

    public function execute(\Closure $block = null) {
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
    private $_redisClient, $_commands, $_initialized;

    public function __construct(Client $redisClient) {
        $this->_initialized = false;
        $this->_redisClient = $redisClient;
        $this->_commands    = array();
    }

    private function initialize() {
        if ($this->_initialized === false) {
            $this->_redisClient->multi();
            $this->_initialized = true;
        }
    }

    public function __call($method, $arguments) {
        $this->initialize();
        $command = $this->_redisClient->createCommand($method, $arguments);
        if ($this->_redisClient->executeCommand($command) === 'QUEUED') {
            $this->_commands[] = $command;
        }
        else {
            // TODO: ...
            throw ClientException('Unexpected condition');
        }
    }

    public function execute(\Closure $block = null) {
        $blockException = null;
        $returnValues   = array();

        try {
            if ($block !== null) {
                $block($this);
            }
            $execReply = $this->_redisClient->exec();
            for ($i = 0; $i < count($execReply); $i++) {
                $returnValues[] = $this->_commands[$i]->parseResponse($execReply[$i]);
            }
        }
        catch (\Exception $exception) {
            $blockException = $exception;
        }

        if ($blockException !== null) {
            throw $blockException;
        }

        return $returnValues;
    }
}

/* ------------------------------------------------------------------------- */

class ConnectionParameters {
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 6379;
    private $_parameters;

    public function __construct($parameters) {
        $parameters = $parameters !== null ? $parameters : array();
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
            'password' => self::getParamOrDefault($parameters, 'password')
        );
    }

    public function __get($parameter) {
        return $this->_parameters[$parameter];
    }
}

interface IConnection {
    public function connect();
    public function disconnect();
    public function isConnected();
    public function writeCommand(Command $command);
    public function readResponse(Command $command);
}

class Connection implements IConnection {
    const CONNECTION_TIMEOUT = 2;
    const READ_WRITE_TIMEOUT = 5;

    private $_params, $_socket, $_initCmds;

    public function __construct(ConnectionParameters $parameters) {
        $this->_params   = $parameters;
        $this->_initCmds = array();
    }

    public function __destruct() {
        $this->disconnect();
    }

    public function isConnected() {
        return is_resource($this->_socket);
    }

    public function connect() {
        if ($this->isConnected()) {
            throw new ClientException('Connection already estabilished');
        }
        $uri = sprintf('tcp://%s:%d/', $this->_params->host, $this->_params->port);
        $this->_socket = @stream_socket_client($uri, $errno, $errstr, self::CONNECTION_TIMEOUT);
        if (!$this->_socket) {
            throw new ClientException(trim($errstr), $errno);
        }
        stream_set_timeout($this->_socket, self::READ_WRITE_TIMEOUT);

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

    public function writeCommand(Command $command) {
        fwrite($this->getSocket(), $command());
    }

    public function readResponse(Command $command) {
        $socket   = $this->getSocket();
        $handler  = Response::getPrefixHandler(fgetc($socket));
        $response = $handler($socket);
        return $response !== 'QUEUED' ? $command->parseResponse($response) : $response;
    }

    public function rawCommand($rawCommandData, $closesConnection = false) {
        $socket = $this->getSocket();
        fwrite($socket, $rawCommandData);
        if ($closesConnection) {
            return;
        }
        $handler = Response::getPrefixHandler(fgetc($socket));
        return $handler($socket);
    }

    public function getSocket() {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->_socket;
    }

    public function __toString() {
        return sprintf('%s:%d', $this->_params->host, $this->_params->port);
    }
}

class ConnectionCluster implements IConnection, \IteratorAggregate {
    // TODO: find a clean way to handle connection failures of single nodes.

    private $_pool, $_ring;

    public function __construct() {
        $this->_pool = array();
        $this->_ring = new Utilities\HashRing();
    }

    public function __destruct() {
        $this->disconnect();
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
        $this->_pool[] = $connection;
        $this->_ring->add($connection);
    }

    private function getConnection(Command $command) {
        if ($command->canBeHashed() === false) {
            throw new ClientException(
                sprintf("Cannot send '%s' commands to a cluster of connections.", $command->getCommandId())
            );
        }
        return $this->_ring->get($command->getHash());
    }

    public function getConnectionById($id = null) {
        return $this->_pool[$id === null ? 0 : $id];
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
}

/* ------------------------------------------------------------------------- */

abstract class RedisServerProfile {
    const DEFAULT_SERVER_PROFILE = '\Predis\RedisServer__V1_2';
    private $_registeredCommands;

    public function __construct() {
        $this->_registeredCommands = $this->getSupportedCommands();
    }

    public abstract function getVersion();

    protected abstract function getSupportedCommands();

    public static function getDefault() {
        $defaultProfile = self::DEFAULT_SERVER_PROFILE;
        return new $defaultProfile();
    }

    public function createCommand($method, $arguments = array()) {
        $commandClass = $this->_registeredCommands[$method];

        if ($commandClass === null) {
            throw new ClientException("'$method' is not a registered Redis command");
        }

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
}

class RedisServer__V1_0 extends RedisServerProfile {
    public function getVersion() { return 1.0; }
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
            'keys'               => '\Predis\Commands\Keys',
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
            'shutdown'              => '\Predis\Commands\Shutdown'
        );
    }
}

class RedisServer__V1_2 extends RedisServer__V1_0 {
    public function getVersion() { return 1.2; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* commands operating on string values */
            'mset'                    => '\Predis\Commands\SetMultiple',
                'setMultiple'         => '\Predis\Commands\SetMultiple',
            'msetnx'                  => '\Predis\Commands\SetMultiplePreserve',
                'setMultiplePreserve' => '\Predis\Commands\SetMultiplePreserve',

            /* commands operating on lists */
            'rpoplpush'        => '\Predis\Commands\ListPushTailPopFirst',
                'listPopLastPushHead'  => '\Predis\Commands\ListPopLastPushHead',

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
                'zsetRemoveRangeByScore'    => '\Predis\Commands\ZSetRemoveRangeByScore'
        ));
    }
}

class RedisServer__Futures extends RedisServer__V1_2 {
    public function getVersion() { return 0; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            'multi'     => '\Predis\Commands\Multi',
            'exec'      => '\Predis\Commands\Exec'
        ));
    }
}

/* ------------------------------------------------------------------------- */

namespace Predis\Utilities;

class HashRing {
    const DEFAULT_REPLICAS = 128;
    private $_ring, $_ringKeys, $_replicas;

    public function __construct($replicas = self::DEFAULT_REPLICAS) {
        $this->_replicas = $replicas;
        $this->_ring     = array();
        $this->_ringKeys = array();
    }

    public function add($node) {
        $nodeHash = (string) $node;
        $replicas = $this->_replicas;
        for ($i = 0; $i < $replicas; $i++) {
            $key = crc32($nodeHash . ':' . $i);
            $this->_ring[$key] = $node;
        }
        ksort($this->_ring, SORT_NUMERIC);
        $this->_ringKeys = array_keys($this->_ring);
    }

    public function remove($node) {
        $nodeHash = (string) $node;
        $replicas = $this->_replicas;
        for ($i = 0; $i < $replicas; $i++) {
            $key = crc32($nodeHash . ':' . $i);
            unset($this->_ring[$key]);
            $this->_ringKeys = array_filter($this->_ringKeys, function($rk) use($key) {
                return $rk !== $key;
            });
        }
    }

    public function get($key) {
        return $this->_ring[$this->getNodeKey($key)];
    }

    private function getNodeKey($key) {
        $ringKeys = $this->_ringKeys;

        $upper = count($ringKeys) - 1;
        $lower = 0;
        $index = 0;

        while ($lower <= $upper) {
            $index = ($lower + $upper) / 2;
            $item  = $ringKeys[$index];
            if ($item > $key) {
                $upper = $index - 1;
            }
            else if ($item < $key) {
                $lower = $index + 1;
            }
            else {
                return $index;
            }
        }
        return $ringKeys[$upper];
    }
}

/* ------------------------------------------------------------------------- */

namespace Predis\Commands;

/* miscellaneous commands */
class Ping extends  \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}

class DoEcho extends \Predis\BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}

class Auth extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}

/* connection handling */
class Quit extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}

/* commands operating on string values */
class Set extends \Predis\BulkCommand {
    public function getCommandId() { return 'SET'; }
}

class SetPreserve extends \Predis\BulkCommand {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetMultiple extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSET'; }
}

class SetMultiplePreserve extends \Predis\MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Get extends \Predis\InlineCommand {
    public function getCommandId() { return 'GET'; }
}

class GetMultiple extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
}

class GetSet extends \Predis\BulkCommand {
    public function getCommandId() { return 'GETSET'; }
}

class Increment extends \Predis\InlineCommand {
    public function getCommandId() { return 'INCR'; }
}

class IncrementBy extends \Predis\InlineCommand {
    public function getCommandId() { return 'INCRBY'; }
}

class Decrement extends \Predis\InlineCommand {
    public function getCommandId() { return 'DECR'; }
}

class DecrementBy extends \Predis\InlineCommand {
    public function getCommandId() { return 'DECRBY'; }
}

class Exists extends \Predis\InlineCommand {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Delete extends \Predis\InlineCommand {
    public function getCommandId() { return 'DEL'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Type extends \Predis\InlineCommand {
    public function getCommandId() { return 'TYPE'; }
}

/* commands operating on the key space */
class Keys extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
    public function parseResponse($data) { 
        // TODO: is this behaviour correct?
        return strlen($data) > 0 ? explode(' ', $data) : array();
    }
}

class RandomKey extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}

class Rename extends \Predis\InlineCommand {
    // TODO: doesn't RENAME break the hash-based client-side sharding?
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}

class RenamePreserve extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Expire extends \Predis\InlineCommand {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ExpireAt extends \Predis\InlineCommand {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}

class DatabaseSize extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}

class TimeToLive extends \Predis\InlineCommand {
    public function getCommandId() { return 'TTL'; }
}

/* commands operating on lists */
class ListPushTail extends \Predis\BulkCommand {
    public function getCommandId() { return 'RPUSH'; }
}

class ListPushHead extends \Predis\BulkCommand {
    public function getCommandId() { return 'LPUSH'; }
}

class ListLength extends \Predis\InlineCommand {
    public function getCommandId() { return 'LLEN'; }
}

class ListRange extends \Predis\InlineCommand {
    public function getCommandId() { return 'LRANGE'; }
}

class ListTrim extends \Predis\InlineCommand {
    public function getCommandId() { return 'LTRIM'; }
}

class ListIndex extends \Predis\InlineCommand {
    public function getCommandId() { return 'LINDEX'; }
}

class ListSet extends \Predis\BulkCommand {
    public function getCommandId() { return 'LSET'; }
}

class ListRemove extends \Predis\BulkCommand {
    public function getCommandId() { return 'LREM'; }
}

class ListPopLastPushHead extends \Predis\BulkCommand {
    public function getCommandId() { return 'RPOPLPUSH'; }
}

class ListPopFirst extends \Predis\InlineCommand {
    public function getCommandId() { return 'LPOP'; }
}

class ListPopLast extends \Predis\InlineCommand {
    public function getCommandId() { return 'RPOP'; }
}

/* commands operating on sets */
class SetAdd extends \Predis\BulkCommand {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetRemove extends \Predis\BulkCommand {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetPop  extends \Predis\InlineCommand {
    public function getCommandId() { return 'SPOP'; }
}

class SetMove extends \Predis\BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetCardinality extends \Predis\InlineCommand {
    public function getCommandId() { return 'SCARD'; }
}

class SetIsMember extends \Predis\BulkCommand {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetIntersection extends \Predis\InlineCommand {
    public function getCommandId() { return 'SINTER'; }
}

class SetIntersectionStore extends \Predis\InlineCommand {
    public function getCommandId() { return 'SINTERSTORE'; }
}

class SetUnion extends \Predis\InlineCommand {
    public function getCommandId() { return 'SUNION'; }
}

class SetUnionStore extends \Predis\InlineCommand {
    public function getCommandId() { return 'SUNIONSTORE'; }
}

class SetDifference extends \Predis\InlineCommand {
    public function getCommandId() { return 'SDIFF'; }
}

class SetDifferenceStore extends \Predis\InlineCommand {
    public function getCommandId() { return 'SDIFFSTORE'; }
}

class SetMembers extends \Predis\InlineCommand {
    public function getCommandId() { return 'SMEMBERS'; }
}

class SetRandomMember extends \Predis\InlineCommand {
    public function getCommandId() { return 'SRANDMEMBER'; }
}

/* commands operating on sorted sets */
class ZSetAdd extends \Predis\BulkCommand {
    public function getCommandId() { return 'ZADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ZSetIncrementBy extends \Predis\BulkCommand {
    public function getCommandId() { return 'ZINCRBY'; }
}

class ZSetRemove extends \Predis\BulkCommand {
    public function getCommandId() { return 'ZREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ZSetRange extends \Predis\InlineCommand {
    public function getCommandId() { return 'ZRANGE'; }
    public function parseResponse($data) {
        $arguments = $this->getArguments();
        if (count($arguments) === 4) {
            if (strtolower($arguments[3]) === 'withscores') {
                $result = array();
                for ($i = 0; $i < count($data); $i++) {
                    $result[] = array($data[$i], $data[++$i]);
                }
                return $result;
            }
        }
        return $data;
    }
}

class ZSetReverseRange extends \Predis\Commands\ZSetRange {
    public function getCommandId() { return 'ZREVRANGE'; }
}

class ZSetRangeByScore extends \Predis\InlineCommand {
    public function getCommandId() { return 'ZRANGEBYSCORE'; }
}

class ZSetCardinality extends \Predis\InlineCommand {
    public function getCommandId() { return 'ZCARD'; }
}

class ZSetScore extends \Predis\BulkCommand {
    public function getCommandId() { return 'ZSCORE'; }
}

class ZSetRemoveRangeByScore extends \Predis\InlineCommand {
    public function getCommandId() { return 'ZREMRANGEBYSCORE'; }
}

/* multiple databases handling commands */
class SelectDatabase extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SELECT'; }
}

class MoveKey extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class FlushDatabase extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}

class FlushAll extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}

/* sorting */
class Sort extends \Predis\InlineCommand {
    public function getCommandId() { return 'SORT'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 1) {
            return $arguments;
        }

        // TODO: add more parameters checks
        $query = array($arguments[0]);
        $sortParams = $arguments[1];

        if (isset($sortParams['by'])) {
            $query[] = 'BY ' . $sortParams['by'];
        }
        if (isset($sortParams['get'])) {
            $query[] = 'GET ' . $sortParams['get'];
        }
        if (isset($sortParams['limit']) && is_array($sortParams['limit'])) {
            $query[] = 'LIMIT ' . $sortParams['limit'][0] . ' ' . $sortParams['limit'][1];
        }
        if (isset($sortParams['sort'])) {
            $query[] = strtoupper($sortParams['sort']);
        }
        if (isset($sortParams['alpha']) && $sortParams['alpha'] == true) {
            $query[] = 'ALPHA';
        }
        if (isset($sortParams['store']) && $sortParams['store'] == true) {
            $query[] = 'STORE ' . $sortParams['store'];
        }

        return $query;
    }
}

/* persistence control commands */
class Save extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}

class BackgroundSave extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGSAVE'; }
}

class LastSave extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}

class Shutdown extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}

/* remote server control commands */
class Info extends \Predis\InlineCommand {
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

class SlaveOf extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SLAVEOF'; }
    public function filterArguments(Array $arguments) {
        return count($arguments) === 0 ? array('NO ONE') : $arguments;
    }
}

class Multi extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MULTI'; }
}

class Exec extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'EXEC'; }
}
?>
