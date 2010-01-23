<?php
class PredisException extends Exception { }
class Predis_ClientException extends PredisException { }
class Predis_ServerException extends PredisException { }
class Predis_MalformedServerResponse extends Predis_ServerException { }

/* ------------------------------------------------------------------------- */

class Predis_Client {
    private $_connection, $_serverProfile;

    public function __construct($parameters = null, Predis_RedisServerProfile $serverProfile = null) {
        $this->setProfile(
            $serverProfile === null 
                ? Predis_RedisServerProfile::getDefault() 
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
        if ($argc > 0 && !is_string($lastArg) && is_subclass_of($lastArg, 'Predis_RedisServerProfile')) {
            $serverProfile = array_pop($argv);
            $argc--;
        }

        if ($argc === 0) {
            throw new Predis_ClientException('Missing connection parameters');
        }

        return new Predis_Client($argc === 1 ? $argv[0] : $argv, $serverProfile);
    }

    private function setupConnection($parameters) {
        if ($parameters !== null && !(is_array($parameters) || is_string($parameters))) {
            throw new Predis_ClientException('Invalid parameters type (array or string expected)');
        }

        if (is_array($parameters) && isset($parameters[0])) {
            $cluster = new Predis_ConnectionCluster();
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
        $params     = new Predis_ConnectionParameters($parameters);
        $connection = new Predis_Connection($params);

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

    private function setConnection(Predis_IConnection $connection) {
        $this->_connection = $connection;
    }

    public function setProfile(Predis_RedisServerProfile $serverProfile) {
        $this->_serverProfile = $serverProfile;
    }

    public function getProfile() {
        return $this->_serverProfile;
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

    private function executeCommandInternal(Predis_IConnection $connection, Predis_Command $command) {
        $connection->writeCommand($command);
        if ($command->closesConnection()) {
            return $connection->disconnect();
        }
        return $connection->readResponse($command);
    }

    public function executeCommand(Predis_Command $command) {
        return self::executeCommandInternal($this->_connection, $command);
    }

    public function executeCommandOnShards(Predis_Command $command) {
        $replies = array();
        if (is_a($this->_connection, 'Predis_ConnectionCluster')) {
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
        if (is_a($this->_connection, 'Predis_ConnectionCluster')) {
            throw new Predis_ClientException('Cannot send raw commands when connected to a cluster of Redis servers');
        }
        return $this->_connection->rawCommand($rawCommandData, $closesConnection);
    }

    public function pipeline($pipelineBlock = null) {
        $pipeline = new Predis_CommandPipeline($this);
        return $pipelineBlock !== null ? $pipeline->execute($pipelineBlock) : $pipeline;
    }

    public function multiExec($multiExecBlock = null) {
        $multiExec = new Predis_MultiExecBlock($this);
        return $multiExecBlock !== null ? $multiExec->execute($multiExecBlock) : $multiExec;
    }
}

/* ------------------------------------------------------------------------- */

abstract class Predis_Command {
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

    public final function invoke() {
        return $this->serializeRequest($this->getCommandId(), $this->getArguments());
    }
}

abstract class Predis_InlineCommand extends Predis_Command {
    public function serializeRequest($command, $arguments) {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            $arguments[0] = implode($arguments[0], ' ');
        }
        return $command . ' ' . implode($arguments, ' ') . Predis_Response::NEWLINE;
    }
}

abstract class Predis_BulkCommand extends Predis_Command {
    public function serializeRequest($command, $arguments) {
        $data = array_pop($arguments);
        if (is_array($data)) {
            $data = implode($data, ' ');
        }
        return $command . ' ' . implode($arguments, ' ') . ' ' . strlen($data) . 
            Predis_Response::NEWLINE . $data . Predis_Response::NEWLINE;
    }
}

abstract class Predis_MultiBulkCommand extends Predis_Command {
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

        $buffer[] = '*' . ((string) count($cmd_args) + 1) . Predis_Response::NEWLINE;
        $buffer[] = '$' . strlen($command) . Predis_Response::NEWLINE . $command . Predis_Response::NEWLINE;
        foreach ($cmd_args as $argument) {
            $buffer[] = '$' . strlen($argument) . Predis_Response::NEWLINE . $argument . Predis_Response::NEWLINE;
        }

        return implode('', $buffer);
    }
}

/* ------------------------------------------------------------------------- */

class Predis_Response {
    const NEWLINE = "\r\n";
    const OK      = 'OK';
    const ERROR   = 'ERR';
    const QUEUED  = 'QUEUED';
    const NULL    = 'nil';

    private static $_prefixHandlers;

    private static function initializePrefixHandlers() {
        return array(
            // status
            '+' => array('Predis_Response', 'handleStatus'), 

            // error
            '-' => array('Predis_Response', 'handleError'), 

            // bulk
            '$' => array('Predis_Response', 'handleBulk'), 

            // multibulk
            '*' => array('Predis_Response', 'handleMultiBulk'), 

            // integer
            ':' => array('Predis_Response', 'handleInteger')
        );
    }

    public static function handleStatus($socket, $prefix, $status) {
        if ($status === Predis_Response::OK) {
            return true;
        }
        else if ($status === Predis_Response::QUEUED) {
            return new Predis_ResponseQueued();
        }
        return $status;
    }

    public static function handleError($socket, $prefix, $errorMessage) {
        throw new Predis_ServerException(substr($errorMessage, 4));
    }

    public static function handleBulk($socket, $prefix, $dataLength) {
        if (!is_numeric($dataLength)) {
            throw new Predis_ClientException("Cannot parse '$dataLength' as data length");
        }

        if ($dataLength > 0) {
            $value = stream_get_contents($socket, $dataLength);
            if ($value === false) {
                throw new Predis_ClientException('An error has occurred while reading from the network stream');
            }
            fread($socket, 2);
            return $value;
        }
        else if ($dataLength == 0) {
            fread($socket, 2);
            return '';
        }

        return null;
    }

    public static function handleMultiBulk($socket, $prefix, $rawLength) {
        if (!is_numeric($rawLength)) {
            throw new Predis_ClientException("Cannot parse '$rawLength' as data length");
        }

        $listLength = (int) $rawLength;
        if ($listLength === -1) {
            return null;
        }

        $list = array();

        if ($listLength > 0) {
            for ($i = 0; $i < $listLength; $i++) {
                $list[] = Predis_Response::read($socket);
            }
        }

        return $list;
    }

    public static function handleInteger($socket, $prefix, $number) {
        if (is_numeric($number)) {
            return (int) $number;
        }
        else {
            if ($number !== Response::NULL) {
                throw new Predis_ClientException("Cannot parse '$number' as numeric response");
            }
            return null;
        }
    }

    public static function read($socket) {
        $header  = fgets($socket);
        if ($header === false) {
           throw new Predis_ClientException('An error has occurred while reading from the network stream');
        }

        $prefix  = $header[0];
        $payload = substr($header, 1, -2);

        if (!isset(self::$_prefixHandlers)) {
            self::$_prefixHandlers = self::initializePrefixHandlers();
        }
        if (!isset(self::$_prefixHandlers[$prefix])) {
            throw new Predis_MalformedServerResponse("Unknown prefix '$prefix'");
        }

        $handler = self::$_prefixHandlers[$prefix];
        return call_user_func($handler, $socket, $prefix, $payload);
    }
}

class Predis_ResponseQueued {
    public $queued = true;

    public function __toString() {
        return Predis_Response::QUEUED;
    }
}

class Predis_CommandPipeline {
    private $_redisClient, $_pipelineBuffer, $_returnValues, $_running;

    public function __construct(Predis_Client $redisClient) {
        $this->_redisClient    = $redisClient;
        $this->_pipelineBuffer = array();
        $this->_returnValues   = array();
    }

    public function __call($method, $arguments) {
        $command = $this->_redisClient->createCommand($method, $arguments);
        $this->recordCommand($command);
    }

    private function recordCommand(Predis_Command $command) {
        $this->_pipelineBuffer[] = $command;
    }

    private function getRecordedCommands() {
        return $this->_pipelineBuffer;
    }

    public function flushPipeline() {
        $sizeofPipe = count($this->_pipelineBuffer);
        if ($sizeofPipe === 0) {
            return;
        }

        $connection = $this->_redisClient->getConnection();
        $commands   = &$this->_pipelineBuffer;

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }
        for ($i = 0; $i < $sizeofPipe; $i++) {
            $this->_returnValues[] = $connection->readResponse($commands[$i]);
            unset($commands[$i]);
        }
    }

    private function setRunning($bool) {
        if ($bool == true && $this->_running == true) {
            throw new Predis_ClientException("This pipeline is already opened");
        }

        $this->_running = $bool;
    }

    public function execute($block = null) {
        if ($block && !is_callable($block)) {
            throw new RuntimeException('Argument passed must be a callable object');
        }

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
    private $_redisClient, $_commands, $_initialized;

    public function __construct(Predis_Client $redisClient) {
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
        $command  = $this->_redisClient->createCommand($method, $arguments);
        $response = $this->_redisClient->executeCommand($command);
        if (isset($response->queued)) {
            $this->_commands[] = $command;
            return $response;
        }
        else {
            throw new Predis_ClientException('The server did not respond with a QUEUED status reply');
        }
    }

    public function execute($block = null) {
        if ($block && !is_callable($block)) {
            throw new RuntimeException('Argument passed must be a callable object');
        }

        $blockException = null;
        $returnValues   = array();

        try {
            if ($block !== null) {
                $block($this);
            }

            $execReply = $this->_redisClient->exec();
            $commands  = &$this->_commands;
            $sizeofReplies = count($execReply);

            if ($sizeofReplies !== count($commands)) {
                // TODO: think of a better exception message
                throw new Predis_ClientException("Out-of-sync");
            }

            for ($i = 0; $i < $sizeofReplies; $i++) {
                $returnValues[] = $commands[$i]->parseResponse($execReply[$i]);
                unset($commands[$i]);
            }
        }
        catch (Exception $exception) {
            $blockException = $exception;
        }

        if ($blockException !== null) {
            throw $blockException;
        }

        return $returnValues;
    }
}

/* ------------------------------------------------------------------------- */

class Predis_ConnectionParameters {
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
            throw new Predis_ClientException("Invalid URI: $uri");
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
                    case 'connection_timeout':
                        $details['connection_timeout'] = $v;
                        break;
                    case 'read_write_timeout':
                        $details['read_write_timeout'] = $v;
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
            'connection_timeout' => self::getParamOrDefault($parameters, 'connection_timeout'), 
            'read_write_timeout' => self::getParamOrDefault($parameters, 'read_write_timeout'), 
        );
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
}

class Predis_Connection implements Predis_IConnection {
    const CONNECTION_TIMEOUT = 2;

    private $_params, $_socket, $_initCmds;

    public function __construct(Predis_ConnectionParameters $parameters) {
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
            throw new Predis_ClientException('Connection already estabilished');
        }
        $uri = sprintf('tcp://%s:%d/', $this->_params->host, $this->_params->port);
        $connectionTimeout = isset($this->_params->connection_timeout) 
            ? $this->_params->connection_timeout
            : self::CONNECTION_TIMEOUT;
        $this->_socket = @stream_socket_client($uri, $errno, $errstr, $connectionTimeout);
        if (!$this->_socket) {
            throw new Predis_ClientException(trim($errstr), $errno);
        }

        if (isset($this->_params->read_write_timeout)) {
            stream_set_timeout($this->_socket, $this->_params->read_write_timeout);
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

    public function writeCommand(Predis_Command $command) {
        $written = fwrite($this->getSocket(), $command->invoke());
        if ($written === false){
           throw new Predis_ClientException(sprintf(
               'An error has occurred while writing command %s on the network stream'),
               $command->getCommandId()
           );
        }
    }

    public function readResponse(Predis_Command $command) {
        $response = Predis_Response::read($this->getSocket());
        return isset($response->queued) ? $response : $command->parseResponse($response);
    }

    public function rawCommand($rawCommandData, $closesConnection = false) {
        $socket = $this->getSocket();
        $written = fwrite($socket, $rawCommandData);
        if ($written === false){
           throw new Predis_ClientException('An error has occurred while writing a raw command on the network stream');
        }
        if ($closesConnection) {
            return;
        }
        return Predis_Response::read($socket);
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

class Predis_ConnectionCluster implements Predis_IConnection, IteratorAggregate {
    // TODO: find a clean way to handle connection failures of single nodes.

    private $_pool, $_ring;

    public function __construct() {
        $this->_pool = array();
        $this->_ring = new Utilities_HashRing();
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

    public function add(Predis_Connection $connection) {
        $this->_pool[] = $connection;
        $this->_ring->add($connection);
    }

    private function getConnection(Predis_Command $command) {
        if ($command->canBeHashed() === false) {
            throw new Predis_ClientException(
                sprintf("Cannot send '%s' commands to a cluster of connections.", $command->getCommandId())
            );
        }
        return $this->_ring->get($command->getHash());
    }

    public function getConnectionById($id = null) {
        return $this->_pool[$id === null ? 0 : $id];
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

    private static function predisServerProfiles() {
        return array(
            '1.0'     => 'Predis_RedisServer_v1_0',
            '1.2'     => 'Predis_RedisServer_v1_2',
            'default' => 'Predis_RedisServer_v1_2',
            'dev'     => 'Predis_RedisServer_vNext',
        );
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

    public function compareWith($version, $operator = null) {
        // one could expect that PHP's version_compare would behave 
        // the same way if invoked with 2 arguments or 3 arguments 
        // with the third being NULL, but it is not like that.
        // TODO: since version_compare considers 1 < 1.0 < 1.0.0, 
        //       we might need to revise the behavior of this method.
        return ($operator === null 
            ? version_compare($this, $version)
            : version_compare($this, $version, $operator)
        );
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
            throw new Predis_ClientException("Cannot register '$command' as it is not a valid Redis command");
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

class Predis_RedisServer_v1_0 extends Predis_RedisServerProfile {
    public function getVersion() { return '1.0'; }
    public function getSupportedCommands() {
        return array(
            /* miscellaneous commands */
            'ping'      => 'Predis_Commands_Ping',
            'echo'      => 'Predis_Commands_DoEcho',
            'auth'      => 'Predis_Commands_Auth',

            /* connection handling */
            'quit'      => 'Predis_Commands_Quit',

            /* commands operating on string values */
            'set'                     => 'Predis_Commands_Set',
            'setnx'                   => 'Predis_Commands_SetPreserve',
                'setPreserve'         => 'Predis_Commands_SetPreserve',
            'get'                     => 'Predis_Commands_Get',
            'mget'                    => 'Predis_Commands_GetMultiple',
                'getMultiple'         => 'Predis_Commands_GetMultiple',
            'getset'                  => 'Predis_Commands_GetSet',
                'getSet'              => 'Predis_Commands_GetSet',
            'incr'                    => 'Predis_Commands_Increment',
                'increment'           => 'Predis_Commands_Increment',
            'incrby'                  => 'Predis_Commands_IncrementBy',
                'incrementBy'         => 'Predis_Commands_IncrementBy',
            'decr'                    => 'Predis_Commands_Decrement',
                'decrement'           => 'Predis_Commands_Decrement',
            'decrby'                  => 'Predis_Commands_DecrementBy',
                'decrementBy'         => 'Predis_Commands_DecrementBy',
            'exists'                  => 'Predis_Commands_Exists',
            'del'                     => 'Predis_Commands_Delete',
                'delete'              => 'Predis_Commands_Delete',
            'type'                    => 'Predis_Commands_Type',

            /* commands operating on the key space */
            'keys'               => 'Predis_Commands_Keys',
            'randomkey'          => 'Predis_Commands_RandomKey',
                'randomKey'      => 'Predis_Commands_RandomKey',
            'rename'             => 'Predis_Commands_Rename',
            'renamenx'           => 'Predis_Commands_RenamePreserve',
                'renamePreserve' => 'Predis_Commands_RenamePreserve',
            'expire'             => 'Predis_Commands_Expire',
            'expireat'           => 'Predis_Commands_ExpireAt',
                'expireAt'       => 'Predis_Commands_ExpireAt',
            'dbsize'             => 'Predis_Commands_DatabaseSize',
                'databaseSize'   => 'Predis_Commands_DatabaseSize',
            'ttl'                => 'Predis_Commands_TimeToLive',
                'timeToLive'     => 'Predis_Commands_TimeToLive',

            /* commands operating on lists */
            'rpush'            => 'Predis_Commands_ListPushTail',
                'pushTail'     => 'Predis_Commands_ListPushTail',
            'lpush'            => 'Predis_Commands_ListPushHead',
                'pushHead'     => 'Predis_Commands_ListPushHead',
            'llen'             => 'Predis_Commands_ListLength',
                'listLength'   => 'Predis_Commands_ListLength',
            'lrange'           => 'Predis_Commands_ListRange',
                'listRange'    => 'Predis_Commands_ListRange',
            'ltrim'            => 'Predis_Commands_ListTrim',
                'listTrim'     => 'Predis_Commands_ListTrim',
            'lindex'           => 'Predis_Commands_ListIndex',
                'listIndex'    => 'Predis_Commands_ListIndex',
            'lset'             => 'Predis_Commands_ListSet',
                'listSet'      => 'Predis_Commands_ListSet',
            'lrem'             => 'Predis_Commands_ListRemove',
                'listRemove'   => 'Predis_Commands_ListRemove',
            'lpop'             => 'Predis_Commands_ListPopFirst',
                'popFirst'     => 'Predis_Commands_ListPopFirst',
            'rpop'             => 'Predis_Commands_ListPopLast',
                'popLast'      => 'Predis_Commands_ListPopLast',

            /* commands operating on sets */
            'sadd'                      => 'Predis_Commands_SetAdd', 
                'setAdd'                => 'Predis_Commands_SetAdd',
            'srem'                      => 'Predis_Commands_SetRemove', 
                'setRemove'             => 'Predis_Commands_SetRemove',
            'spop'                      => 'Predis_Commands_SetPop',
                'setPop'                => 'Predis_Commands_SetPop',
            'smove'                     => 'Predis_Commands_SetMove', 
                'setMove'               => 'Predis_Commands_SetMove',
            'scard'                     => 'Predis_Commands_SetCardinality', 
                'setCardinality'        => 'Predis_Commands_SetCardinality',
            'sismember'                 => 'Predis_Commands_SetIsMember', 
                'setIsMember'           => 'Predis_Commands_SetIsMember',
            'sinter'                    => 'Predis_Commands_SetIntersection', 
                'setIntersection'       => 'Predis_Commands_SetIntersection',
            'sinterstore'               => 'Predis_Commands_SetIntersectionStore', 
                'setIntersectionStore'  => 'Predis_Commands_SetIntersectionStore',
            'sunion'                    => 'Predis_Commands_SetUnion', 
                'setUnion'              => 'Predis_Commands_SetUnion',
            'sunionstore'               => 'Predis_Commands_SetUnionStore', 
                'setUnionStore'         => 'Predis_Commands_SetUnionStore',
            'sdiff'                     => 'Predis_Commands_SetDifference', 
                'setDifference'         => 'Predis_Commands_SetDifference',
            'sdiffstore'                => 'Predis_Commands_SetDifferenceStore', 
                'setDifferenceStore'    => 'Predis_Commands_SetDifferenceStore',
            'smembers'                  => 'Predis_Commands_SetMembers', 
                'setMembers'            => 'Predis_Commands_SetMembers',
            'srandmember'               => 'Predis_Commands_SetRandomMember', 
                'setRandomMember'       => 'Predis_Commands_SetRandomMember',

            /* multiple databases handling commands */
            'select'                => 'Predis_Commands_SelectDatabase', 
                'selectDatabase'    => 'Predis_Commands_SelectDatabase',
            'move'                  => 'Predis_Commands_MoveKey', 
                'moveKey'           => 'Predis_Commands_MoveKey',
            'flushdb'               => 'Predis_Commands_FlushDatabase', 
                'flushDatabase'     => 'Predis_Commands_FlushDatabase',
            'flushall'              => 'Predis_Commands_FlushAll', 
                'flushDatabases'    => 'Predis_Commands_FlushAll',

            /* sorting */
            'sort'                  => 'Predis_Commands_Sort',

            /* remote server control commands */
            'info'                  => 'Predis_Commands_Info',
            'slaveof'               => 'Predis_Commands_SlaveOf', 
                'slaveOf'           => 'Predis_Commands_SlaveOf',

            /* persistence control commands */
            'save'                  => 'Predis_Commands_Save',
            'bgsave'                => 'Predis_Commands_BackgroundSave', 
                'backgroundSave'    => 'Predis_Commands_BackgroundSave',
            'lastsave'              => 'Predis_Commands_LastSave', 
                'lastSave'          => 'Predis_Commands_LastSave',
            'shutdown'              => 'Predis_Commands_Shutdown',
        );
    }
}

class Predis_RedisServer_v1_2 extends Predis_RedisServer_v1_0 {
    public function getVersion() { return '1.2'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* commands operating on string values */
            'mset'                    => 'Predis_Commands_SetMultiple',
                'setMultiple'         => 'Predis_Commands_SetMultiple',
            'msetnx'                  => 'Predis_Commands_SetMultiplePreserve',
                'setMultiplePreserve' => 'Predis_Commands_SetMultiplePreserve',

            /* commands operating on lists */
            'rpoplpush'        => 'Predis_Commands_ListPushTailPopFirst',
                'listPopLastPushHead'  => 'Predis_Commands_ListPopLastPushHead',

            /* commands operating on sorted sets */
            'zadd'                          => 'Predis_Commands_ZSetAdd',
                'zsetAdd'                   => 'Predis_Commands_ZSetAdd',
            'zincrby'                       => 'Predis_Commands_ZSetIncrementBy',
                'zsetIncrementBy'           => 'Predis_Commands_ZSetIncrementBy',
            'zrem'                          => 'Predis_Commands_ZSetRemove',
                'zsetRemove'                => 'Predis_Commands_ZSetRemove',
            'zrange'                        => 'Predis_Commands_ZSetRange',
                'zsetRange'                 => 'Predis_Commands_ZSetRange',
            'zrevrange'                     => 'Predis_Commands_ZSetReverseRange',
                'zsetReverseRange'          => 'Predis_Commands_ZSetReverseRange',
            'zrangebyscore'                 => 'Predis_Commands_ZSetRangeByScore',
                'zsetRangeByScore'          => 'Predis_Commands_ZSetRangeByScore',
            'zcard'                         => 'Predis_Commands_ZSetCardinality',
                'zsetCardinality'           => 'Predis_Commands_ZSetCardinality',
            'zscore'                        => 'Predis_Commands_ZSetScore',
                'zsetScore'                 => 'Predis_Commands_ZSetScore',
            'zremrangebyscore'              => 'Predis_Commands_ZSetRemoveRangeByScore',
                'zsetRemoveRangeByScore'    => 'Predis_Commands_ZSetRemoveRangeByScore',

            /* persistence control commands */
            'bgrewriteaof'                      =>  'Predis_Commands_BackgroundRewriteAppendOnlyFile',
            'backgroundRewriteAppendOnlyFile'   =>  'Predis_Commands_BackgroundRewriteAppendOnlyFile',
        ));
    }
}

class Predis_RedisServer_vNext extends Predis_RedisServer_v1_2 {
    public function getVersion() { return '1.3'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* miscellaneous commands */
            'multi'     => 'Predis_Commands_Multi',
            'exec'      => 'Predis_Commands_Exec',

            /* commands operating on lists */
            'blpop'                     => 'Predis_Commands_ListPopFirstBlocking',
                'popFirstBlocking'      => 'Predis_Commands_ListPopFirstBlocking',
            'brpop'                     => 'Predis_Commands_ListPopLastBlocking',
                'popLastBlocking'       => 'Predis_Commands_ListPopLastBlocking',
        ));
    }
}

/* ------------------------------------------------------------------------- */

class Utilities_HashRing {
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
            $newRing = array();
            foreach ($this->_ringKeys as $rk) {
                if ($rk !== $key) {
                    $newRing[] = $rk;
                }
            }
            $this->_ringKeys = $newRing;
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

/* miscellaneous commands */
class Predis_Commands_Ping extends  Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}

class Predis_Commands_DoEcho extends Predis_BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}

class Predis_Commands_Auth extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}

/* connection handling */
class Predis_Commands_Quit extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}

/* commands operating on string values */
class Predis_Commands_Set extends Predis_BulkCommand {
    public function getCommandId() { return 'SET'; }
}

class Predis_Commands_SetPreserve extends Predis_BulkCommand {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetMultiple extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSET'; }
}

class Predis_Commands_SetMultiplePreserve extends Predis_MultiBulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MSETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_Get extends Predis_InlineCommand {
    public function getCommandId() { return 'GET'; }
}

class Predis_Commands_GetMultiple extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
}

class Predis_Commands_GetSet extends Predis_BulkCommand {
    public function getCommandId() { return 'GETSET'; }
}

class Predis_Commands_Increment extends Predis_InlineCommand {
    public function getCommandId() { return 'INCR'; }
}

class Predis_Commands_IncrementBy extends Predis_InlineCommand {
    public function getCommandId() { return 'INCRBY'; }
}

class Predis_Commands_Decrement extends Predis_InlineCommand {
    public function getCommandId() { return 'DECR'; }
}

class Predis_Commands_DecrementBy extends Predis_InlineCommand {
    public function getCommandId() { return 'DECRBY'; }
}

class Predis_Commands_Exists extends Predis_InlineCommand {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_Delete extends Predis_InlineCommand {
    public function getCommandId() { return 'DEL'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_Type extends Predis_InlineCommand {
    public function getCommandId() { return 'TYPE'; }
}

/* commands operating on the key space */
class Predis_Commands_Keys extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
    public function parseResponse($data) { 
        // TODO: is this behaviour correct?
        return strlen($data) > 0 ? explode(' ', $data) : array();
    }
}

class Predis_Commands_RandomKey extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}

class Predis_Commands_Rename extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}

class Predis_Commands_RenamePreserve extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_Expire extends Predis_InlineCommand {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_ExpireAt extends Predis_InlineCommand {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_DatabaseSize extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}

class Predis_Commands_TimeToLive extends Predis_InlineCommand {
    public function getCommandId() { return 'TTL'; }
}

/* commands operating on lists */
class Predis_Commands_ListPushTail extends Predis_BulkCommand {
    public function getCommandId() { return 'RPUSH'; }
}

class Predis_Commands_ListPushHead extends Predis_BulkCommand {
    public function getCommandId() { return 'LPUSH'; }
}

class Predis_Commands_ListLength extends Predis_InlineCommand {
    public function getCommandId() { return 'LLEN'; }
}

class Predis_Commands_ListRange extends Predis_InlineCommand {
    public function getCommandId() { return 'LRANGE'; }
}

class Predis_Commands_ListTrim extends Predis_InlineCommand {
    public function getCommandId() { return 'LTRIM'; }
}

class Predis_Commands_ListIndex extends Predis_InlineCommand {
    public function getCommandId() { return 'LINDEX'; }
}

class Predis_Commands_ListSet extends Predis_BulkCommand {
    public function getCommandId() { return 'LSET'; }
}

class Predis_Commands_ListRemove extends Predis_BulkCommand {
    public function getCommandId() { return 'LREM'; }
}

class Predis_Commands_ListPopLastPushHead extends Predis_InlineCommand {
    public function getCommandId() { return 'RPOPLPUSH'; }
}

class Predis_Commands_ListPopLastPushHeadBulk extends Predis_BulkCommand {
    public function getCommandId() { return 'RPOPLPUSH'; }
}

class Predis_Commands_ListPopFirst extends Predis_InlineCommand {
    public function getCommandId() { return 'LPOP'; }
}

class Predis_Commands_ListPopLast extends Predis_InlineCommand {
    public function getCommandId() { return 'RPOP'; }
}

class Predis_Commands_ListPopFirstBlocking extends Predis_InlineCommand {
    public function getCommandId() { return 'BLPOP'; }
}

class Predis_Commands_ListPopLastBlocking extends Predis_InlineCommand {
    public function getCommandId() { return 'BRPOP'; }
}

/* commands operating on sets */
class Predis_Commands_SetAdd extends Predis_BulkCommand {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetRemove extends Predis_BulkCommand {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetPop  extends Predis_InlineCommand {
    public function getCommandId() { return 'SPOP'; }
}

class Predis_Commands_SetMove extends Predis_BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetCardinality extends Predis_InlineCommand {
    public function getCommandId() { return 'SCARD'; }
}

class Predis_Commands_SetIsMember extends Predis_BulkCommand {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_SetIntersection extends Predis_InlineCommand {
    public function getCommandId() { return 'SINTER'; }
}

class Predis_Commands_SetIntersectionStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SINTERSTORE'; }
}

class Predis_Commands_SetUnion extends Predis_InlineCommand {
    public function getCommandId() { return 'SUNION'; }
}

class Predis_Commands_SetUnionStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SUNIONSTORE'; }
}

class Predis_Commands_SetDifference extends Predis_InlineCommand {
    public function getCommandId() { return 'SDIFF'; }
}

class Predis_Commands_SetDifferenceStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SDIFFSTORE'; }
}

class Predis_Commands_SetMembers extends Predis_InlineCommand {
    public function getCommandId() { return 'SMEMBERS'; }
}

class Predis_Commands_SetRandomMember extends Predis_InlineCommand {
    public function getCommandId() { return 'SRANDMEMBER'; }
}

/* commands operating on sorted sets */
class Predis_Commands_ZSetAdd extends Predis_BulkCommand {
    public function getCommandId() { return 'ZADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_ZSetIncrementBy extends Predis_BulkCommand {
    public function getCommandId() { return 'ZINCRBY'; }
}

class Predis_Commands_ZSetRemove extends Predis_BulkCommand {
    public function getCommandId() { return 'ZREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_ZSetRange extends Predis_InlineCommand {
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

class Predis_Commands_ZSetReverseRange extends Predis_Commands_ZSetRange {
    public function getCommandId() { return 'ZREVRANGE'; }
}

class Predis_Commands_ZSetRangeByScore extends Predis_InlineCommand {
    public function getCommandId() { return 'ZRANGEBYSCORE'; }
}

class Predis_Commands_ZSetCardinality extends Predis_InlineCommand {
    public function getCommandId() { return 'ZCARD'; }
}

class Predis_Commands_ZSetScore extends Predis_BulkCommand {
    public function getCommandId() { return 'ZSCORE'; }
}

class Predis_Commands_ZSetRemoveRangeByScore extends Predis_InlineCommand {
    public function getCommandId() { return 'ZREMRANGEBYSCORE'; }
}

/* multiple databases handling commands */
class Predis_Commands_SelectDatabase extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SELECT'; }
}

class Predis_Commands_MoveKey extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Commands_FlushDatabase extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}

class Predis_Commands_FlushAll extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}

/* sorting */
class Predis_Commands_Sort extends Predis_InlineCommand {
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
class Predis_Commands_Save extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}

class Predis_Commands_BackgroundSave extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGSAVE'; }
    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}

class Predis_Commands_BackgroundRewriteAppendOnlyFile extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGREWRITEAOF'; }
    public function parseResponse($data) {
        return $data == 'Background append only file rewriting started';
    }
}

class Predis_Commands_LastSave extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}

class Predis_Commands_Shutdown extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}

/* remote server control commands */
class Predis_Commands_Info extends Predis_InlineCommand {
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

class Predis_Commands_SlaveOf extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SLAVEOF'; }
    public function filterArguments(Array $arguments) {
        return count($arguments) === 0 ? array('NO ONE') : $arguments;
    }
}

class Predis_Commands_Multi extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MULTI'; }
}

class Predis_Commands_Exec extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'EXEC'; }
}
?>
