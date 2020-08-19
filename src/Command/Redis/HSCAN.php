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
 * @link http://redis.io/commands/hscan
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class HSCAN extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HSCAN';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            $options = $this->prepareOptions(array_pop($arguments));
            $arguments = array_merge($arguments, $options);
        }

        parent::setArguments($arguments);
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options List of options.
     *
     * @return array
     */
    protected function prepareOptions($options)
    {
        $options = array_change_key_case($options, CASE_UPPER);
        $normalized = array();

        if (!empty($options['MATCH'])) {
            $normalized[] = 'MATCH';
            $normalized[] = $options['MATCH'];
        }

        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }

        return $normalized;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_array($data)) {
            $fields = $data[1];
            $result = array();

            for ($i = 0; $i < count($fields); ++$i) {
                $result[$fields[$i]] = $fields[++$i];
            }

            $data[1] = $result;
        }

        return $data;
    }
}
