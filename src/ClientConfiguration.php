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
            ['name' => 'CuckooFilter', 'commandPrefix' => 'CF'],
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
