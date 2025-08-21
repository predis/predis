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

namespace Predis\Protocol\Parser;

use InvalidArgumentException;
use Predis\Protocol\Parser\Strategy\ParserStrategyInterface;
use Predis\Protocol\Parser\Strategy\Resp2Strategy;
use Predis\Protocol\Parser\Strategy\Resp3Strategy;

class ParserStrategyResolver implements ParserStrategyResolverInterface
{
    /**
     * @var string[]
     */
    protected $protocolStrategyMapping = [
        2 => Resp2Strategy::class,
        3 => Resp3Strategy::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function resolve(int $protocolVersion): ParserStrategyInterface
    {
        if (!array_key_exists($protocolVersion, $this->protocolStrategyMapping)) {
            throw new InvalidArgumentException('Invalid protocol version given.');
        }

        $strategy = $this->protocolStrategyMapping[$protocolVersion];

        return new $strategy();
    }
}
