<?php

namespace Predis;

use Predis\Commands\ICommand;
use Predis\Network\IConnection;
use Predis\Network\IConnectionSingle;
use Predis\Network\ConnectionCluster;
use Predis\Profiles\ServerProfile;
use Predis\Profiles\IServerProfile;

class Client {
    const VERSION = '0.7.0-dev';
    private $_options, $_connectionFactory, $_profile, $_connection;

    public function __construct($parameters = null, $options = null) {
        $options = $this->filterOptions($options ?: new ClientOptions());
        $this->_options = $options;
        $this->_profile = $options->profile;
        $this->_connectionFactory = $options->connections;
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
            return new ClientOptions(array('profile' => ServerProfile::get($options)));
        }
        throw new \InvalidArgumentException("Invalid type for client options");
    }

    private function initializeConnection($parameters = null) {
        if ($parameters === null) {
            return $this->createConnection(new ConnectionParameters());
        }
        if (is_array($parameters)) {
            if (isset($parameters[0])) {
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
        if ($parameters instanceof IConnection) {
            return $parameters;
        }
        return $this->createConnection($parameters);
    }

    private function createConnection($parameters) {
        $connection = $this->_connectionFactory->create($parameters);
        $this->pushInitCommands($connection);
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

    public function getConnectionFactory() {
        return $this->_connectionFactory;
    }

    public function getClientFor($connectionAlias) {
        if (!Helpers::isCluster($this->_connection)) {
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
        $this->_connection->connect();
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
        if ($id === null) {
            return $this->_connection;
        }
        $connection = $this->_connection;
        $isCluster = Helpers::isCluster($connection);
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
        if (Helpers::isCluster($this->_connection)) {
            $replies = array();
            foreach ($this->_connection as $connection) {
                $replies[] = $connection->executeCommand($command);
            }
            return $replies;
        }
        return array($this->_connection->executeCommand($command));
    }

    private function sharedInitializer($argv, $initializer) {
        switch (count($argv)) {
            case 0:
                return $this->$initializer();
            case 1:
                list($arg0) = $argv;
                return is_array($arg0)
                    ? $this->$initializer($arg0)
                    : $this->$initializer(null, $arg0);
            case 2:
                list($arg0, $arg1) = $argv;
                return $this->$initializer($arg0, $arg1);
            default:
                return $this->$initializer($this, $argv);
        }
    }

    public function pipeline(/* arguments */) {
        return $this->sharedInitializer(func_get_args(), 'initPipeline');
    }

    private function initPipeline(Array $options = null, $pipelineBlock = null) {
        $pipeline = null;
        if (isset($options)) {
            if (isset($options['safe']) && $options['safe'] == true) {
                $connection = $this->_connection;
                $pipeline = new PipelineContext($this,
                    Helpers::isCluster($connection)
                        ? new Pipeline\SafeClusterExecutor($connection)
                        : new Pipeline\SafeExecutor($connection)
                );
            }
            else {
                $pipeline = new PipelineContext($this);
            }
        }
        return $this->pipelineExecute(
            $pipeline ?: new PipelineContext($this), $pipelineBlock
        );
    }

    private function pipelineExecute(PipelineContext $pipeline, $block) {
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
}
