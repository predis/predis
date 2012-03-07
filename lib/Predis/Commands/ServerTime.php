<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

/**
 * @link http://redis.io/commands/time
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerTime extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'TIME';
    }

    /**
     * {@inheritdoc}
     */
    protected function canBeHashed()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data instanceof \Iterator ? iterator_to_array($data) : $data;
    }
}
