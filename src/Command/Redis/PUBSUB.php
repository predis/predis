<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see http://redis.io/commands/pubsub
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PUBSUB extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PUBSUB';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        switch (strtolower($this->getArgument(0))) {
            case 'numsub':
                return self::processNumsub($data);

            default:
                return $data;
        }
    }

    /**
     * Returns the processed response to PUBSUB NUMSUB.
     *
     * @param array $channels List of channels
     *
     * @return array
     */
    protected static function processNumsub(array $channels)
    {
        $processed = array();
        $count = count($channels);

        for ($i = 0; $i < $count; ++$i) {
            $processed[$channels[$i]] = $channels[++$i];
        }

        return $processed;
    }
}
