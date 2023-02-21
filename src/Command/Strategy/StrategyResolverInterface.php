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

namespace Predis\Command\Strategy;

interface StrategyResolverInterface
{
    /**
     * Resolves subcommand strategy.
     *
     * @param  string                      $commandId
     * @param  string                      $subcommandId
     * @return SubcommandStrategyInterface
     */
    public function resolve(string $commandId, string $subcommandId): SubcommandStrategyInterface;
}
