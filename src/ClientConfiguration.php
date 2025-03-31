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

namespace Predis;

class ClientConfiguration
{
    /**
     * @var array{modules: array}|string[][]
     */
    private static $config = [
        'modules' => [
            ['name' => 'Json', 'commandPrefix' => 'JSON'],
            ['name' => 'BloomFilter', 'commandPrefix' => 'BF'],
            ['name' => 'CuckooFilter', 'commandPrefix' => 'CF'],
            ['name' => 'CountMinSketch', 'commandPrefix' => 'CMS'],
            ['name' => 'TDigest', 'commandPrefix' => 'TDIGEST'],
            ['name' => 'TopK', 'commandPrefix' => 'TOPK'],
            ['name' => 'Search', 'commandPrefix' => 'FT'],
            ['name' => 'TimeSeries', 'commandPrefix' => 'TS'],
        ],
    ];

    /**
     * Returns available modules with configuration.
     *
     * @return array|string[][]
     */
    public static function getModules(): array
    {
        return self::$config['modules'];
    }
}
