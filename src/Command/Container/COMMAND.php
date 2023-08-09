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

namespace Predis\Command\Container;

use Predis\Command\Argument\CommandListFilter;

/**
 * @method array getKeysAndFlags(string $command, string ...$args)
 * @method array info(string ...$commandName)
 * @method array list(CommandListFilter $filter = null)
 */
class COMMAND extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'COMMAND';
    }
}
