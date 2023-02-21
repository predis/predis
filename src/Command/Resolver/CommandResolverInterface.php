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

namespace Predis\Command\Resolver;

interface CommandResolverInterface
{
    /**
     * Resolves command object from given command ID.
     *
     * @param  string $commandID Command ID of virtual method call
     * @return string FQDN of corresponding command object
     */
    public function resolve(string $commandID): ?string;
}
