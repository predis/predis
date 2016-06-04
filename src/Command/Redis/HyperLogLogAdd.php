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

use Predis\Command\Command;

/**
 * @link http://redis.io/commands/pfadd
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HyperLogLogAdd extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PFADD';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $arguments = self::normalizeVariadic($arguments);

        parent::setArguments($arguments);
    }
}
