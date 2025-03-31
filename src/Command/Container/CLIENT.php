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

namespace Predis\Command\Container;

use Predis\Response\Status;

/**
 * @method string getName()
 * @method Status kill(...$arguments)
 * @method string list(string $type = null, int ...$clientId)
 * @method Status noEvict(bool $enable = null)
 * @method Status noTouch(bool $enable = null)
 * @method Status setInfo(string $modifier = null, string $value = null)
 * @method Status setName(string $connectionName)
 */
class CLIENT extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'CLIENT';
    }
}
