<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Strategy\StrategyResolverInterface;
use Predis\Command\Strategy\SubcommandStrategyResolver;

/**
 * @see https://redis.io/commands/?name=xgroup
 *
 * Container command corresponds to any XGROUP *.
 * Represents any XGROUP command with subcommand as first argument.
 */
class XGROUP extends RedisCommand
{
    /**
     * @var array
     */
    private $splitWordsDictionary = [
        'CREATECONSUMER' => 'Create Consumer',
        'DELCONSUMER' => 'Del Consumer',
        'SETID' => 'Set Id',
    ];

    /**
     * @var StrategyResolverInterface
     */
    private $strategyResolver;

    public function __construct()
    {
        $this->strategyResolver = new SubcommandStrategyResolver(' ');
    }

    public function getId()
    {
        return 'XGROUP';
    }

    public function setArguments(array $arguments)
    {
        if (array_key_exists($arguments[0], $this->splitWordsDictionary)) {
            $subcommandId = $this->splitWordsDictionary[$arguments[0]];
        } else {
            $subcommandId = strtolower($arguments[0]);
        }

        $strategy = $this->strategyResolver->resolve('X Group', $subcommandId);
        $arguments = $strategy->processArguments($arguments);

        parent::setArguments($arguments);
    }
}
