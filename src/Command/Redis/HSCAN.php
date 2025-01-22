<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see http://redis.io/commands/hscan
 */
class HSCAN extends RedisCommand
{
    /**
     * @var array
     */
    private $arguments;

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

        $this->arguments = $arguments;
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
        $normalized = [];

        if (!empty($options['MATCH'])) {
            $normalized[] = 'MATCH';
            $normalized[] = $options['MATCH'];
        }

        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }

        if (!empty($options['NOVALUES']) && true === $options['NOVALUES']) {
            $normalized[] = 'NOVALUES';
        }

        return $normalized;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (!in_array('NOVALUES', $this->arguments, true)) {
            if (is_array($data)) {
                $fields = $data[1];
                $result = [];

                for ($i = 0; $i < count($fields); ++$i) {
                    $result[$fields[$i]] = $fields[++$i];
                }

                $data[1] = $result;
            }
        }

        return $data;
    }
}
