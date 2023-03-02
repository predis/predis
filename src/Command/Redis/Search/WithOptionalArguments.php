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

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\ArgumentBuilder;
use Predis\Command\Command as RedisCommand;

abstract class WithOptionalArguments extends RedisCommand
{
    /**
     * Builds an array of optional arguments according to command arguments' specification.
     *
     * @param  ArgumentBuilder $builder
     * @param  array           $arguments
     * @return array
     */
    public function buildOptionalArguments(ArgumentBuilder $builder, array $arguments): array
    {
        $argumentsMapping = $this->getOptionalArguments();

        foreach ($arguments as $key => $argumentValue) {
            if (!empty($argumentValue)) {
                $argumentName = $argumentsMapping[$key];

                $builder = $builder->{$argumentName}($argumentValue);
            }
        }

        return $builder->toArray();
    }

    /**
     * Specifies array of optional arguments for given command.
     *
     * @return array Array of command optional arguments according to documentation, order should be kept
     */
    abstract public function getOptionalArguments(): array;
}
