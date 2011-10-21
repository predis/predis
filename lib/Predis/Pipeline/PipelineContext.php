<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Predis\Client;
use Predis\Helpers;
use Predis\ClientException;
use Predis\Commands\ICommand;

/**
 * Abstraction of a pipeline context where write and read operations
 * of commands and their replies over the network are pipelined.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PipelineContext
{
    private $_client;
    private $_executor;

    private $_pipeline = array();
    private $_replies  = array();
    private $_running  = false;

    /**
     * @param Client Client instance used by the context.
     * @param array Options for the context initialization.
     */
    public function __construct(Client $client, Array $options = null)
    {
        $this->_client = $client;
        $this->_executor = $this->getExecutor($client, $options ?: array());
    }

    /**
     * Returns a pipeline executor depending on the kind of the underlying
     * connection and the passed options.
     *
     * @param Client Client instance used by the context.
     * @param array Options for the context initialization.
     * @return IPipelineExecutor
     */
    protected function getExecutor(Client $client, Array $options)
    {
        if (!$options) {
            return new StandardExecutor();
        }

        if (isset($options['executor'])) {
            $executor = $options['executor'];
            if (!$executor instanceof IPipelineExecutor) {
                throw new \InvalidArgumentException(
                    'The executor option accepts only instances ' .
                    'of Predis\Pipeline\IPipelineExecutor'
                );
            }
            return $executor;
        }

        if (isset($options['safe']) && $options['safe'] == true) {
            $isCluster = Helpers::isCluster($client->getConnection());
            return $isCluster ? new SafeClusterExecutor() : new SafeExecutor();
        }

        return new StandardExecutor();
    }

    /**
     * Queues a command into the pipeline buffer.
     *
     * @param string $method Command ID.
     * @param array $arguments Arguments for the command.
     * @return PipelineContext
     */
    public function __call($method, $arguments)
    {
        $command = $this->_client->createCommand($method, $arguments);
        $this->recordCommand($command);

        return $this;
    }

    /**
     * Queues a command instance into the pipeline buffer.
     */
    protected function recordCommand(ICommand $command)
    {
        $this->_pipeline[] = $command;
    }

    /**
     * Queues a command instance into the pipeline buffer.
     */
    public function executeCommand(ICommand $command)
    {
        $this->recordCommand($command);
    }

    /**
     * Flushes the queued commands by writing the buffer to Redis and reading
     * all the replies into the reply buffer.
     *
     * @return PipelineContext
     */
    public function flushPipeline()
    {
        if (count($this->_pipeline) > 0) {
            $connection = $this->_client->getConnection();
            $replies = $this->_executor->execute($connection, $this->_pipeline);
            $this->_replies = array_merge($this->_replies, $replies);
            $this->_pipeline = array();
        }

        return $this;
    }

    /**
     * Marks the running status of the pipeline.
     *
     * @param Boolean $bool True if the pipeline is running.
     *                      False if the pipeline is not running.
     */
    private function setRunning($bool)
    {
        if ($bool === true && $this->_running === true) {
            throw new ClientException("This pipeline is already opened");
        }
        $this->_running = $bool;
    }

    /**
     * Handles the actual execution of the whole pipeline.
     *
     * @param mixed $callable Callback for execution.
     * @return array
     */
    public function execute($callable = null)
    {
        if ($callable && !is_callable($callable)) {
            throw new \InvalidArgumentException('Argument passed must be a callable object');
        }

        $this->setRunning(true);
        $pipelineBlockException = null;

        try {
            if ($callable !== null) {
                $callable($this);
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

        return $this->_replies;
    }
}
