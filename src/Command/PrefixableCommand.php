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

namespace Predis\Command;

abstract class PrefixableCommand extends Command implements PrefixableCommandInterface
{
    /**
     * {@inheritDoc}
     */
    abstract public function getId();

    /**
     * {@inheritDoc}
     */
    abstract public function prefixKeys($prefix);

    /**
     * Applies prefix for all arguments.
     *
     * @param  string $prefix
     * @return void
     */
    public function applyPrefixForAllArguments(string $prefix): void
    {
        $this->setRawArguments(
            array_map(static function ($key) use ($prefix) {
                return $prefix . $key;
            }, $this->getArguments())
        );
    }

    /**
     * Applies prefix for first argument.
     *
     * @param  string $prefix
     * @return void
     */
    public function applyPrefixForFirstArgument(string $prefix): void
    {
        $arguments = $this->getArguments();
        $arguments[0] = $prefix . $arguments[0];
        $this->setRawArguments($arguments);
    }

    /**
     * Applies prefix for interleaved arguments.
     *
     * @param  string $prefix
     * @return void
     */
    public function applyPrefixForInterleavedArgument(string $prefix): void
    {
        if ($arguments = $this->getArguments()) {
            $length = count($arguments);

            for ($i = 0; $i < $length; $i += 2) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $this->setRawArguments($arguments);
        }
    }

    /**
     * Applies prefix for all keys except last one.
     *
     * @param  string $prefix
     * @return void
     */
    public function applyPrefixSkippingLastArgument(string $prefix): void
    {
        if ($arguments = $this->getArguments()) {
            $length = count($arguments);

            for ($i = 0; $i < $length - 1; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $this->setRawArguments($arguments);
        }
    }

    /**
     * Applies prefix for all keys except first one.
     *
     * @param  string $prefix
     * @return void
     */
    public function applyPrefixSkippingFirstArgument(string $prefix): void
    {
        if ($arguments = $this->getArguments()) {
            $length = count($arguments);

            for ($i = 1; $i < $length; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $this->setRawArguments($arguments);
        }
    }
}
