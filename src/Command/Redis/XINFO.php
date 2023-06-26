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
use Predis\Command\Strategy\SubcommandStrategyInterface;
use Predis\Command\Strategy\SubcommandStrategyResolver;

class XINFO extends RedisCommand
{
    /**
     * @var SubcommandStrategyResolver
     */
    private $strategyResolver;

    /**
     * @var SubcommandStrategyInterface
     */
    private $strategy;

    public function __construct()
    {
        $this->strategyResolver = new SubcommandStrategyResolver(' ');
    }

    public function getId()
    {
        return 'XINFO';
    }

    public function setArguments(array $arguments)
    {
        $this->strategy = $this->strategyResolver->resolve('X Info', strtolower($arguments[0]));

        parent::setArguments($this->strategy->processArguments($arguments));
    }

    public function parseResponse($data)
    {
        return (null !== $this->strategy) ? $this->strategy->parseResponse($data) : $data;
    }
}
