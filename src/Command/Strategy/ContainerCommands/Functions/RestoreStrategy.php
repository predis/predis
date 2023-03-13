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
use UnexpectedValueException;

class RestoreStrategy implements SubcommandStrategyInterface
{
    /**
     * @var string[]
     */
    private $modifierEnum = [
        'flush' => 'FLUSH',
        'append' => 'APPEND',
        'replace' => 'REPLACE',
    ];

    /**
     * {@inheritDoc}
     */
    public function processArguments(array $arguments): array
    {
        if (array_key_exists(2, $arguments)) {
            if (in_array(strtoupper($arguments[2]), $this->modifierEnum, true)) {
                $arguments[2] = $this->modifierEnum[strtolower($arguments[2])];
            } else {
                $enumValues = implode(', ', $this->modifierEnum);
                throw new UnexpectedValueException("Modifier argument accepts only: {$enumValues} values");
            }
        }

        return $arguments;
    }
}
