<?php

namespace Predis\Pipeline;

use Predis\Client;
use Predis\Helpers;
use Predis\ClientException;
use Predis\Commands\ICommand;

class PipelineContext {
    private $_client, $_pipelineBuffer, $_returnValues, $_running, $_executor;

    public function __construct(Client $client, Array $options = null) {
        $this->_client         = $client;
        $this->_executor       = $this->getExecutor($client, $options ?: array());
        $this->_pipelineBuffer = array();
        $this->_returnValues   = array();
    }

    protected function getExecutor(Client $client, Array $options) {
        if (!$options) {
            return new StandardExecutor();
        }
        if (isset($options['executor'])) {
            $executor = $options['executor'];
            if (!$executor instanceof IPipelineExecutor) {
                throw new \ArgumentException();
            }
            return $executor;
        }
        if (isset($options['safe']) && $options['safe'] == true) {
            $isCluster = Helpers::isCluster($client->getConnection());
            return $isCluster ? new SafeClusterExecutor() : new SafeExecutor();
        }
        return new StandardExecutor();
    }

    public function __call($method, $arguments) {
        $command = $this->_client->createCommand($method, $arguments);
        $this->recordCommand($command);
        return $this;
    }

    protected function recordCommand(ICommand $command) {
        $this->_pipelineBuffer[] = $command;
    }

    public function flushPipeline() {
        if (count($this->_pipelineBuffer) > 0) {
            $connection = $this->_client->getConnection();
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
