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

namespace Predis\Command;

/**
 * Defines a command whose keys can be prefixed.
 */
interface PrefixableCommandInterface extends CommandInterface
{
    /**
     * Prefixes all the keys found in the arguments of the command.
     *
     * @param string $prefix String used to prefix the keys.
     */
    public function prefixKeys($prefix);
}
