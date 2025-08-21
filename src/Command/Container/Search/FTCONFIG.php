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

namespace Predis\Command\Container\Search;

use Predis\Command\Container\AbstractContainer;
use Predis\Response\Status;

/**
 * @method array  get(string $option)
 * @method array  help(string $option)
 * @method Status set(string $option, $value)
 */
class FTCONFIG extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'FTCONFIG';
    }
}
