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

namespace Predis\Command\Redis\Search;

use Predis\Command\PrefixableCommand as RedisCommand;
use Predis\Command\Redis\Utils\CommandUtility;

class FTHYBRID extends RedisCommand
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'FT.HYBRID';
    }

    public function setArguments(array $arguments)
    {
        [$index, $query] = $arguments;

        parent::setArguments(array_merge(
            [$index],
            $query->toArray()
        ));
    }

    public function parseResponse($data)
    {
        $response = CommandUtility::arrayToDictionary($data, null, false);

        foreach ($response['results'] as $key => $result) {
            $response['results'][$key] = CommandUtility::arrayToDictionary($result);
        }

        return $response;
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
