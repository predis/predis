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

namespace Predis\Command\Redis\TopK;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/topk.list/
 *
 * Return full list of items in Top K list.
 */
class TOPKLIST extends RedisCommand
{
    public function getId()
    {
        return 'TOPK.LIST';
    }

    public function setArguments(array $arguments)
    {
        if (!empty($arguments[1])) {
            $arguments[1] = 'WITHCOUNT';
        }

        parent::setArguments($arguments);
        $this->filterArguments();
    }

    public function parseResponse($data)
    {
        if ($this->isWithCountModifier()) {
            $result = [];

            for ($i = 0, $iMax = count($data); $i < $iMax; ++$i) {
                if (array_key_exists($i + 1, $data)) {
                    $result[(string) $data[$i]] = $data[++$i];
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
        return $this->parseResponse($data);
    }

    /**
     * Checks for the presence of the WITHCOUNT modifier.
     *
     * @return bool
     */
    private function isWithCountModifier(): bool
    {
        $arguments = $this->getArguments();
        $lastArgument = (!empty($arguments)) ? $arguments[count($arguments) - 1] : null;

        return is_string($lastArgument) && strtoupper($lastArgument) === 'WITHCOUNT';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
