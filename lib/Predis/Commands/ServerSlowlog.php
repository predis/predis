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

use Predis\Iterators\MultiBulkResponse;

/**
 * @link http://redis.io/commands/slowlog
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerSlowlog extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SLOWLOG';
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
        if (($iterable = $data instanceof \Iterator) || is_array($data)) {
            // NOTE: we consume iterable multibulk replies inplace since it is not
            // possible to do anything fancy on sub-elements.
            $log = array();

            foreach ($data as $index => $entry) {
                if ($iterable) {
                    $entry = iterator_to_array($entry);
                }

                $log[$index] = array(
                    'id' => $entry[0],
                    'timestamp' => $entry[1],
                    'duration' => $entry[2],
                    'command' => $iterable ? iterator_to_array($entry[3]) : $entry[3],
                );
            }

            if ($iterable === true) {
                unset($data);
            }

            return $log;
        }

        return $data;
    }
}
