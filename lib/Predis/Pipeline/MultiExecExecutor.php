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

use SplQueue;
use Predis\ClientException;
use Predis\ResponseErrorInterface;
use Predis\ResponseObjectInterface;
use Predis\ResponseQueued;
use Predis\ServerException;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\SingleConnectionInterface;
use Predis\Profile\ServerProfile;
use Predis\Profile\ServerProfileInterface;

/**
 * Implements a pipeline executor that wraps the whole pipeline
 * in a MULTI / EXEC context to make sure that it is executed
 * correctly.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiExecExecutor implements PipelineExecutorInterface
{
    protected $profile;

    /**
     *
     */
    public function __construct(ServerProfileInterface $profile = null)
    {
        $this->setProfile($profile ?: ServerProfile::getDefault());
    }

    /**
     * Allows the pipeline executor to perform operations on the
     * connection before starting to execute the commands stored
     * in the pipeline.
     *
     * @param ConnectionInterface Connection instance.
     */
    protected function checkConnection(ConnectionInterface $connection)
    {
        if (!$connection instanceof SingleConnectionInterface) {
            $class = __CLASS__;
            throw new ClientException("$class can be used only with single connections");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ConnectionInterface $connection, SplQueue $commands)
    {
        $size = count($commands);
        $values = array();

        $this->checkConnection($connection);

        $cmd = $this->profile->createCommand('multi');
        $connection->executeCommand($cmd);

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }

        foreach ($commands as $command) {
            $response = $connection->readResponse($command);

            if ($response instanceof ResponseErrorInterface) {
                $cmd = $this->profile->createCommand('discard');
                $connection->executeCommand($cmd);

                throw new ServerException($response->getMessage());
            }
        }

        $cmd = $this->profile->createCommand('exec');
        $responses = $connection->executeCommand($cmd);

        if (!isset($responses)) {
            throw new ClientException('The underlying transaction has been aborted by the server');
        }

        if (count($responses) !== $size) {
            throw new ClientException("Invalid number of replies [expected: $size - actual: ".count($responses)."]");
        }

        for ($i = 0; $i < $size; $i++) {
            $commandReply = $responses[$i];

            if ($commandReply instanceof ResponseObjectInterface) {
                $values[$i] = $commandReply;
                $commands->dequeue();
            } else {
                if ($commandReply instanceof \Iterator) {
                    $commandReply = iterator_to_array($commandReply);
                }

                $values[$i] = $commands->dequeue()->parseResponse($commandReply);
            }

            unset($responses[$i]);
        }

        return $values;
    }

    /**
     * @param ServerProfileInterface $profile Server profile.
     */
    public function setProfile(ServerProfileInterface $profile)
    {
        if (!$profile->supportsCommands(array('multi', 'exec', 'discard'))) {
            throw new ClientException('The specified server profile must support MULTI, EXEC and DISCARD.');
        }

        $this->profile = $profile;
    }
}
