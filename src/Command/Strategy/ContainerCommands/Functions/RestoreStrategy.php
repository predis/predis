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

namespace Predis\Command\Strategy\ContainerCommands\Functions;

use Predis\Command\Strategy\SubcommandStrategyInterface;

class RestoreStrategy implements SubcommandStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function processArguments(array $arguments): array
    {
        $processedArguments = [$arguments[0], $arguments[1]];

        if (array_key_exists(2, $arguments) && null !== $arguments[2]) {
            $processedArguments[] = strtoupper($arguments[2]);
        }

        return $processedArguments;
    }
}
