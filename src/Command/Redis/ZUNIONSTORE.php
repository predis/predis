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
 * @see http://redis.io/commands/zunionstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZUNIONSTORE extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZUNIONSTORE';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $options = array();
        $argc = count($arguments);

        if ($argc > 2 && is_array($arguments[$argc - 1])) {
            $options = $this->prepareOptions(array_pop($arguments));
        }

        if (is_array($arguments[1])) {
            $arguments = array_merge(
                array($arguments[0], count($arguments[1])),
                $arguments[1]
            );
        }

        parent::setArguments(array_merge($arguments, $options));
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options list of options
     *
     * @return array
     */
    private function prepareOptions($options)
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();

        if (isset($opts['WEIGHTS']) && is_array($opts['WEIGHTS'])) {
            $finalizedOpts[] = 'WEIGHTS';

            foreach ($opts['WEIGHTS'] as $weight) {
                $finalizedOpts[] = $weight;
            }
        }

        if (isset($opts['AGGREGATE'])) {
            $finalizedOpts[] = 'AGGREGATE';
            $finalizedOpts[] = $opts['AGGREGATE'];
        }

        return $finalizedOpts;
    }
}
