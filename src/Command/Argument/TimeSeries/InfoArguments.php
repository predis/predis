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

namespace Predis\Command\Argument\TimeSeries;

use Predis\Command\Argument\ArrayableArgument;

class InfoArguments implements ArrayableArgument
{
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * Is an optional flag to get a more detailed information about the chunks.
     *
     * @return $this
     */
    public function debug(): self
    {
        $this->arguments[] = 'DEBUG';

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
