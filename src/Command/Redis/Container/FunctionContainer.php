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

namespace Predis\Command\Redis\Container;

use Predis\Response\Status;

/**
 * @method Status delete(string $libraryName)
 * @method string dump()
 * @method Status flush(?string $mode = null)
 * @method Status kill()
 * @method array  list(?string $libraryNamePattern = null, bool $withCode = false)
 * @method string load(string $functionCode, bool $replace = 'false')
 * @method Status restore(string $value, ?string $policy = null)
 * @method array  stats()
 */
class FunctionContainer extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'FUNCTIONS';
    }
}
