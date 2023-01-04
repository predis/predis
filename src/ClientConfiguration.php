<?php

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
        ]
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
