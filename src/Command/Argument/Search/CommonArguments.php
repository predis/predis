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

namespace Predis\Command\Argument\Search;

class CommonArguments implements ArgumentBuilder
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Adds default language for documents within an index.
     *
     * @param  string $defaultLanguage
     * @return $this
     */
    public function language(string $defaultLanguage = 'english'): self
    {
        $this->arguments[] = 'LANGUAGE';
        $this->arguments[] = $defaultLanguage;

        return $this;
    }

    /**
     * Selects the dialect version under which to execute the query.
     * If not specified, the query will execute under the default dialect version
     * set during module initial loading or via FT.CONFIG SET command.
     *
     * @param  string $dialect
     * @return $this
     */
    public function dialect(string $dialect): self
    {
        $this->arguments[] = 'DIALECT';
        $this->arguments[] = $dialect;

        return $this;
    }

    /**
     * If set, does not scan and index.
     *
     * @return $this
     */
    public function skipInitialScan(): self
    {
        $this->arguments[] = 'SKIPINITIALSCAN';

        return $this;
    }

    /**
     * Adds an arbitrary, binary safe payload that is exposed to custom scoring functions.
     *
     * @param  string $payload
     * @return $this
     */
    public function payload(string $payload): self
    {
        $this->arguments[] = 'PAYLOAD';
        $this->arguments[] = $payload;

        return $this;
    }

    /**
     * Also returns the relative internal score of each document.
     *
     * @return $this
     */
    public function withScores(): self
    {
        $this->arguments[] = 'WITHSCORES';

        return $this;
    }

    /**
     * Retrieves optional document payloads.
     *
     * @return $this
     */
    public function withPayloads(): self
    {
        $this->arguments[] = 'WITHPAYLOADS';

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
