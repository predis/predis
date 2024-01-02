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

use Predis\Response\Status;

/**
 * @method Status load(string $libraryCode, bool $replace = false, string $config = null)
 * @method Status delete(string $libraryName)
 * @method array  list(bool $withCode = false, int $verboseLevel = 0, string $libraryName = null)
 */
class TFUNCTION extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'TFUNCTION';
    }
}
