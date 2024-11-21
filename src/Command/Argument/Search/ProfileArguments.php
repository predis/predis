<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Search;

use Predis\Command\Argument\ArrayableArgument;

class ProfileArguments implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Adds search context.
     *
     * @return $this
     */
    public function search(): self
    {
        $this->arguments[] = 'SEARCH';

        return $this;
    }

    /**
     * Adds aggregate context.
     *
     * @return $this
     */
    public function aggregate(): self
    {
        $this->arguments[] = 'AGGREGATE';

        return $this;
    }

    /**
     * Removes details of reader iterator.
     *
     * @return $this
     */
    public function limited(): self
    {
        $this->arguments[] = 'LIMITED';

        return $this;
    }

    /**
     * Is query string, as if sent to FT.SEARCH.
     *
     * @param  string $query
     * @return $this
     */
    public function query(string $query): self
    {
        $this->arguments[] = 'QUERY';
        $this->arguments[] = $query;

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
