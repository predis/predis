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

namespace Predis\Command\Argument\Stream;

use Predis\Command\Argument\ArrayableArgument;

class XInfoStreamOptions implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * Modifier provides a more verbose reply.
     * The COUNT option can be used to limit the number of stream and PEL entries that are returned.
     *
     * @param  int|null $count
     * @return self
     */
    public function full(?int $count = null): self
    {
        $this->options[] = 'FULL';

        if (null !== $count) {
            array_push($this->options, 'COUNT', $count);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->options;
    }
}
