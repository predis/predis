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
 * @see http://redis.io/commands/xadd
 */
class XADD extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XADD';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $args = [];

        $args[] = $arguments[0];
        $options = $arguments[3] ?? [];

        if (isset($options['nomkstream']) && $options['nomkstream']) {
            $args[] = 'NOMKSTREAM';
        }

        if (isset($options['trim']) && is_array($options['trim'])) {
            array_push($args, ...$options['trim']);

            if (isset($options['limit'])) {
                $args[] = 'LIMIT';
                $args[] = $options['limit'];
            }
        }

        // ID, default to * to let Redis set it
        $args[] = $arguments[2] ?? '*';
        if (isset($arguments[1]) && is_array($arguments[1])) {
            foreach ($arguments[1] as $key => $val) {
                $args[] = $key;
                $args[] = $val;
            }
        }

        parent::setArguments($args);
    }
}
