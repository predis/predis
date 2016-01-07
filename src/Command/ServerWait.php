<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @link http://redis.io/commands/wait
 *
 * @author Thomas Ploch <profiploch@gmail.com>
 */
class ServerWait extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'WAIT';
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(array $arguments)
    {
        $arguments = self::normalizeArguments($arguments);

        if (count($arguments) === 1) {
            $arguments[] = 0;
        }

        return $arguments;
    }

    /**
     * @inheritDoc
     */
    public function getSlot()
    {
        return false;
    }
}
