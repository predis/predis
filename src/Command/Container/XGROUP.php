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
 * @method Status create(string $key, string $group, string $id, bool $mkStream = false, ?string $entriesRead = null)
 * @method int    createConsumer(string $key, string $group, string $consumer)
 * @method int    delConsumer(string $key, string $group, string $consumer)
 * @method int    destroy(string $key, string $group)
 * @method Status setId(string $key, string $group, string $id, ?string $entriesRead = null)
 */
class XGROUP extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'xgroup';
    }
}
