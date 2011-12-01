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

use Predis\Iterators\MultiBulkResponseTuple;

/**
 * @link http://redis.io/commands/hgetall
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HashGetAll extends PrefixableCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HGETALL';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if ($data instanceof \Iterator) {
            return new MultiBulkResponseTuple($data);
        }

        $result = array();
        for ($i = 0; $i < count($data); $i++) {
            $result[$data[$i]] = $data[++$i];
        }

        return $result;
    }
}
