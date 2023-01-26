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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see http://redis.io/topics/sentinel
 */
class SENTINEL extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SENTINEL';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        $argument = $this->getArgument(0);
        $argument = is_null($argument) ? null : strtolower($argument);

        switch ($argument) {
            case 'masters':
            case 'slaves':
                return self::processMastersOrSlaves($data);

            default:
                return $data;
        }
    }

    /**
     * Returns a processed response to SENTINEL MASTERS or SENTINEL SLAVES.
     *
     * @param array $servers List of Redis servers.
     *
     * @return array
     */
    protected static function processMastersOrSlaves(array $servers)
    {
        foreach ($servers as $idx => $node) {
            $processed = [];
            $count = count($node);

            for ($i = 0; $i < $count; ++$i) {
                $processed[$node[$i]] = $node[++$i];
            }

            $servers[$idx] = $processed;
        }

        return $servers;
    }
}
