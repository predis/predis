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
 * @link http://redis.io/commands/config-set
 * @link http://redis.io/commands/config-get
 * @link http://redis.io/commands/config-resetstat
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerConfig extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'CONFIG';
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
        if ($data instanceof \Iterator) {
            return new MultiBulkResponseTuple($data);
        }

        if (is_array($data)) {
            $result = array();
            for ($i = 0; $i < count($data); $i++) {
                $result[$data[$i]] = $data[++$i];
            }

            return $result;
        }

        return $data;
    }
}
