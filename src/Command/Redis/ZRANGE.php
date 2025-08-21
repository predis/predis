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

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see http://redis.io/commands/zrange
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
        if (count($arguments) === 4) {
            $lastType = gettype($arguments[3]);

            if ($lastType === 'string' && strtoupper($arguments[3]) === 'WITHSCORES') {
                // Used for compatibility with older versions
                $arguments[3] = ['WITHSCORES' => true];
                $lastType = 'array';
            }

            if ($lastType === 'array') {
                $options = $this->prepareOptions(array_pop($arguments));
                $arguments = array_merge($arguments, $options);
            }
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
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = [];

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

        return strtoupper($arguments[3]) === 'WITHSCORES';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if ($this->withScores()) {
            $result = [];

            for ($i = 0; $i < count($data); ++$i) {
                if (is_array($data[$i])) {
                    $result[$data[$i][0]] = $data[$i][1]; // Relay
                } else {
                    $result[$data[$i]] = $data[++$i];
                }
            }

            return $result;
        }

        return $data;
    }

    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parseResp3Response($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $parsedData = [];

        foreach ($data as $element) {
            $parsedData[] = $this->parseResponse($element);
        }

        return $parsedData;
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
