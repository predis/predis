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
 * @see http://redis.io/commands/zpopmin
 */
class ZPOPMIN extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZPOPMIN';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
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

    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parseResp3Response($data)
    {
        $parsedData = [];

        foreach ($data as $element) {
            if (is_array($element)) {
                $parsedData[] = $this->parseResponse($element);
            } else {
                return $this->parseResponse($data);
            }
        }

        return $parsedData;
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
