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
 * @see http://redis.io/commands/zrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZRANGE extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZRANGE';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (4 === count($arguments)) {
            $lastType = gettype($arguments[3]);

            if ('string' === $lastType && 'WITHSCORES' === strtoupper($arguments[3])) {
                // Used for compatibility with older versions
                $arguments[3] = array('WITHSCORES' => true);
                $lastType = 'array';
            }

            if ('array' === $lastType) {
                $options = $this->prepareOptions(array_pop($arguments));
                $arguments = array_merge($arguments, $options);
            }
        }

        parent::setArguments($arguments);
    }

    /**
     * Returns a list of options and modifiers compatible with Redis.
     *
     * @param array $options list of options
     *
     * @return array
     */
    protected function prepareOptions($options)
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();

        if (!empty($opts['WITHSCORES'])) {
            $finalizedOpts[] = 'WITHSCORES';
        }

        return $finalizedOpts;
    }

    /**
     * Checks for the presence of the WITHSCORES modifier.
     *
     * @return bool
     */
    protected function withScores()
    {
        $arguments = $this->getArguments();

        if (count($arguments) < 4) {
            return false;
        }

        return 'WITHSCORES' === strtoupper($arguments[3]);
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if ($this->withScores()) {
            $result = array();

            for ($i = 0; $i < count($data); ++$i) {
                $result[$data[$i]] = $data[++$i];
            }

            return $result;
        }

        return $data;
    }
}
