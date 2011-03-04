<?php

namespace Predis;

use Predis\Commands\ICommand;
use Predis\Network\IConnection;
use Predis\Network\IConnectionSingle;
use Predis\Network\ConnectionCluster;
use Predis\Profiles\ServerProfile;
use Predis\Profiles\IServerProfile;

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
        $connection = self::newConnectionInternal($parameters);
        $connection->setProtocolOption('iterable_multibulk', $options->iterable_multibulk);
        $connection->setProtocolOption('throw_errors', $options->throw_errors);
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

    public function quit() {
        $this->disconnect();
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

    public function pubSubContext(Array $options = null) {
        return new PubSubContext($this, $options);
    }

    private static function ensureDefaultSchemes() {
        if (!isset(self::$_connectionSchemes)) {
            self::$_connectionSchemes = array(
                'tcp'   => '\Predis\Network\StreamConnection',
                'unix'  => '\Predis\Network\StreamConnection',
            );
        }
    }

    public static function defineConnection($scheme, $connectionClass) {
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

    private static function newConnectionInternal(ConnectionParameters $parameters) {
        $connection = self::getConnectionClass($parameters->scheme);
        return new $connection($parameters);
    }

    public static function newConnection($parameters) {
        if (!$parameters instanceof ConnectionParameters) {
            $parameters = new ConnectionParameters($parameters);
        }
        return self::newConnectionInternal($parameters);
    }

    public static function newConnectionByScheme($scheme, $parameters = array()) {
        $connection = self::getConnectionClass($scheme);
        if (!$parameters instanceof ConnectionParameters) {
            $parameters = new ConnectionParameters($parameters);
        }
        return self::newConnection($parameters);
    }
}
