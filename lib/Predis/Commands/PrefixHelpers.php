<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

/**
 * Class that defines a few helpers method for prefixing keys.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PrefixHelpers
{
    /**
     * Applies the specified prefix only the first argument.
     *
     * @param ICommand $command Command instance.
     * @param string $prefix Prefix string.
     */
    public static function first(ICommand $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[0] = "$prefix{$arguments[0]}";
            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to all the arguments.
     *
     * @param ICommand $command Command instance.
     * @param string $prefix Prefix string.
     */
    public static function all(ICommand $command, $prefix)
    {
        $arguments = $command->getArguments();

        foreach ($arguments as &$key) {
            $key = "$prefix$key";
        }

        $command->setRawArguments($arguments);
    }

    /**
     * Applies the specified prefix only to even arguments in the list.
     *
     * @param ICommand $command Command instance.
     * @param string $prefix Prefix string.
     */
    public static function interleaved(ICommand $command, $prefix)
    {
        $arguments = $command->getArguments();
        $length = count($arguments);

        for ($i = 0; $i < $length; $i += 2) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }

        $command->setRawArguments($arguments);
    }

    /**
     * Applies the specified prefix to all the arguments but the last one.
     *
     * @param ICommand $command Command instance.
     * @param string $prefix Prefix string.
     */
    public static function skipLast(ICommand $command, $prefix)
    {
        $arguments = $command->getArguments();
        $length = count($arguments);

        for ($i = 0; $i < $length - 1; $i++) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }

        $command->setRawArguments($arguments);
    }
}
