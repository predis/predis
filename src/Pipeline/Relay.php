<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Predis\Connection\ConnectionInterface;
use Predis\Response\ServerException;
use Relay\Exception;
use SplQueue;

/**
 * Command pipeline that writes commands to the servers but discards responses.
 */
class Relay extends Pipeline
{
    /**
     * {@inheritdoc}
     * @throws ServerException
     */
    protected function executePipeline(ConnectionInterface $connection, SplQueue $commands)
    {
        try {
            $pipeline = $connection->getClient()->pipeline();

            foreach ($commands as $command) {
                $pipeline->rawCommand($command->getId(), ...$command->getArguments());
            }

            return $pipeline->exec();
        } catch (Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
