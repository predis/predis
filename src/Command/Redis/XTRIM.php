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
 * @link http://redis.io/commands/xtrim
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class XTRIM extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XTRIM';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $args = [];
        $options = $arguments[3] ?? [];

        $args[] = $arguments[0];
        // Either e.g. 'MAXLEN' or ['MAXLEN', '~']
        if (is_array($arguments[1])) {
            array_push($args, ...$arguments[1]);
        } else {
            $args[] = $arguments[1];
        }

        $args[] = $arguments[2];
        if (isset($options['limit'])) {
            $args[] = 'LIMIT';
            $args[] = $options['limit'];
        }

        parent::setArguments($args);
    }
}
