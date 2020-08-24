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
 * @link http://redis.io/commands/migrate
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MIGRATE extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'MIGRATE';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (is_array(end($arguments))) {
            foreach (array_pop($arguments) as $modifier => $value) {
                $modifier = strtoupper($modifier);

                if ($modifier === 'COPY' && $value == true) {
                    $arguments[] = $modifier;
                }

                if ($modifier === 'REPLACE' && $value == true) {
                    $arguments[] = $modifier;
                }
            }
        }

        parent::setArguments($arguments);
    }
}
