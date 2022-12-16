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
            ['name' => 'Graph', 'commandPrefix' => 'GRAPH'],
            ['name' => 'Search', 'commandPrefix' => 'FT'],
            ['name' => 'TimeSeries', 'commandPrefix' => 'TS'],
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
