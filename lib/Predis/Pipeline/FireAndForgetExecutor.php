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

use Predis\Network\IConnection;
use Predis\Network\IConnectionReplication;

/**
 * Implements a pipeline executor strategy that writes a list of commands to
 * the connection object but does not read back their replies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class FireAndForgetExecutor implements IPipelineExecutor
{
    /**
     * Allows the pipeline executor to perform operations on the
     * connection before starting to execute the commands stored
     * in the pipeline.
     *
     * @param IConnection Connection instance.
     */
    protected function checkConnection(IConnection $connection)
    {
        if ($connection instanceof IConnectionReplication) {
            $connection->switchTo('master');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(IConnection $connection, &$commands)
    {
        $this->checkConnection($connection);

        foreach ($commands as $command) {
            $connection->writeCommand($command);
        }

        $connection->disconnect();

        return array();
    }
}
