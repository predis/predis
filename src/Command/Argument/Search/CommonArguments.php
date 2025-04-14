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

namespace Predis\Command\Argument\Search;

use Predis\Command\Argument\ArrayableArgument;

class CommonArguments implements ArrayableArgument
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
     * Does not try to use stemming for query expansion but searches the query terms verbatim.
     *
     * @return $this
     */
    public function verbatim(): self
    {
        $this->arguments[] = 'VERBATIM';

        return $this;
    }

    /**
     * Overrides the timeout parameter of the module.
     *
     * @param  int   $timeout
     * @return $this
     */
    public function timeout(int $timeout): self
    {
        $this->arguments[] = 'TIMEOUT';
        $this->arguments[] = $timeout;

        return $this;
    }

    /**
     * Adds an arbitrary, binary safe payload that is exposed to custom scoring functions.
     *
     * @param  int   $offset
     * @param  int   $num
     * @return $this
     */
    public function limit(int $offset, int $num): self
    {
        array_push($this->arguments, 'LIMIT', $offset, $num);

        return $this;
    }

    /**
     * Adds filter expression into index.
     *
     * @param  string $filter
     * @return $this
     */
    public function filter(string $filter): self
    {
        $this->arguments[] = 'FILTER';
        $this->arguments[] = $filter;

        return $this;
    }

    /**
     * Defines one or more value parameters. Each parameter has a name and a value.
     *
     * Example: ['name1', 'value1', 'name2', 'value2'...]
     *
     * @param  array $nameValuesDictionary
     * @return $this
     */
    public function params(array $nameValuesDictionary): self
    {
        $this->arguments[] = 'PARAMS';
        $this->arguments[] = count($nameValuesDictionary);
        $this->arguments = array_merge($this->arguments, $nameValuesDictionary);

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
