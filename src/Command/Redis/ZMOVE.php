<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/zmove/
 *
 * Atomically moves the lowest-scoring (MIN), highest-scoring (MAX) members,
 * or members within a score range (BYSCORE, given as [min, max]) from one
 * sorted set to another, preserving their scores.
 */
class ZMOVE extends RedisCommand
{
    public function getId()
    {
        return 'ZMOVE';
    }

    public function setArguments(array $arguments)
    {
        $processed = array_slice($arguments, 0, 2);
        $selection = $arguments[2] ?? null;

        if (is_array($selection)) {
            array_push($processed, 'BYSCORE', ...array_values($selection));
        } elseif (null !== $selection) {
            $processed[] = strtoupper($selection);
        }

        if (isset($arguments[3])) {
            array_push($processed, 'COUNT', $arguments[3]);
        }

        parent::setArguments($processed);
    }

    /**
     * Converts the list of [member, score] pairs into a member => score map.
     *
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (null === $data) {
            return null;
        }

        $result = [];

        foreach ($data as [$member, $score]) {
            $result[$member] = $score;
        }

        return $result;
    }

    public function parseResp3Response($data)
    {
        return $this->parseResponse($data);
    }

    public function prefixKeys($prefix)
    {
        if ($arguments = $this->getArguments()) {
            $arguments[0] = $prefix . $arguments[0];

            if (isset($arguments[1])) {
                $arguments[1] = $prefix . $arguments[1];
            }

            $this->setRawArguments($arguments);
        }
    }
}
