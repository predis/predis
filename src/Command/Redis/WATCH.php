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
 * @link http://redis.io/commands/watch
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class WATCH extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'WATCH';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            $arguments = $arguments[0];
        }

        parent::setArguments($arguments);
    }
}
