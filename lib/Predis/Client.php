<?php

namespace Predis;

use Predis\Commands\ICommand;
use Predis\Network\IConnection;
use Predis\Network\IConnectionSingle;
use Predis\Profiles\IServerProfile;
use Predis\Profiles\ServerProfile;
use Predis\Pipeline\PipelineContext;
use Predis\Transaction\MultiExecContext;

class Client {
    const VERSION = '0.7.0-dev';

    private $_options;
    private $_profile;
    private $_connectionFactory;
    private $_connection;

    public function __construct($parameters = null, $options = null) {
        $options = $this->filterOptions($options);
        $profile = $options->profile;
        if (isset($options->prefix)) {
            $profile->setProcessor($options->prefix);
        }
        $this->_options = $options;
        $this->_profile = $profile;
        $this->_connectionFactory = $options->connections;
        $this->_connection = $this->initializeConnection($parameters);
    }

    private function filterOptions($options) {
        if ($options === null) {
            return new ClientOptions();
        }
        if (is_array($options)) {
            return new ClientOptions($options);
        }
        if ($options instanceof ClientOptions) {
            return $options;
        }
        if ($options instanceof IServerProfile) {
            return new ClientOptions(array('profile' => $options));
        }
        if (is_string($options)) {
            return new ClientOptions(array('profile' => ServerProfile::get($options)));
        }
        throw new \InvalidArgumentException("Invalid type for client options");
    }

    private function initializeConnection($parameters) {
        if ($parameters === null) {
            return $this->createConnection(new ConnectionParameters());
        }
        if (is_array($parameters)) {
            if (isset($parameters[0])) {
                $cluster = $this->_options->cluster;
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

    protected function createConnection($parameters) {
        $connection = $this->_connectionFactory->create($parameters);
        $parameters = $connection->getParameters();
        if (isset($parameters->password)) {
            $connection->pushInitCommand(
                $this->createCommand('auth', array($parameters->password))
            );
        }
        if (isset($parameters->database)) {
            $connection->pushInitCommand(
                $this->createCommand('select', array($parameters->database))
            );
        }
        return $connection;
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
        if (($connection = $this->getConnection($connectionAlias)) === null) {
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
        if (isset($id)) {
            if (!Helpers::isCluster($this->_connection)) {
                throw new ClientException(
                    'Retrieving connections by alias is supported '.
                    'only with clustered connections'
                );
            }
            return $this->_connection->getConnectionById($id);
        }
        return $this->_connection;
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

    protected function initPipeline(Array $options = null, $pipelineBlock = null) {
        return $this->pipelineExecute(
            new PipelineContext($this, $options), $pipelineBlock
        );
    }

    private function pipelineExecute(PipelineContext $pipeline, $block) {
        return $block !== null ? $pipeline->execute($block) : $pipeline;
    }

    public function multiExec(/* arguments */) {
        return $this->sharedInitializer(func_get_args(), 'initMultiExec');
    }

    protected function initMultiExec(Array $options = null, $block = null) {
        $transaction = new MultiExecContext($this, $options ?: array());
        return isset($block) ? $transaction->execute($block) : $transaction;
    }

    public function pubSub(/* arguments */) {
        return $this->sharedInitializer(func_get_args(), 'initPubSub');
    }

    protected function initPubSub(Array $options = null, $block = null) {
        $pubsub = new PubSubContext($this, $options);
        if (!isset($block)) {
            return $pubsub;
        }
        foreach ($pubsub as $message) {
            if ($block($pubsub, $message) === false) {
                $pubsub->closeContext();
            }
        }
    }

    public function monitor() {
        return new MonitorContext($this);
    }
}
