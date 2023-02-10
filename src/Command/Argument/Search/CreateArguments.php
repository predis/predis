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

use InvalidArgumentException;
use Predis\Command\Argument\ArrayableArgument;

class CreateArguments implements ArrayableArgument
{
    /**
     * @var array
     */
    private $arguments;

    /**
     * Specify data type for given index. To index JSON you must have the RedisJSON module to be installed.
     *
     * @param  string $modifier
     * @return $this
     */
    public function on(string $modifier = 'hash'): self
    {
        if (strtoupper($modifier) === 'HASH' || strtoupper($modifier) === 'JSON') {
            $this->arguments[] = 'ON';
            $this->arguments[] = strtoupper($modifier);

            return $this;
        }

        throw new InvalidArgumentException('Wrong modifier value given. Currently supports: HASH, JSON');
    }

    /**
     * Adds one or more prefixes into index.
     *
     * @param  array $prefixes
     * @return $this
     */
    public function prefix(array $prefixes): self
    {
        $this->arguments[] = 'PREFIX';
        $this->arguments[] = count($prefixes);
        $this->arguments = array_merge($this->arguments, $prefixes);

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
     * Adds default language for documents within an index.
     *
     * @param  string $defaultLanguage
     * @return $this
     */
    public function language(string $defaultLanguage): self
    {
        $this->arguments[] = 'LANGUAGE';
        $this->arguments[] = $defaultLanguage;

        return $this;
    }

    /**
     * Document attribute set as document language.
     *
     * @param  string $languageAttribute
     * @return $this
     */
    public function languageField(string $languageAttribute): self
    {
        $this->arguments[] = 'LANGUAGE_FIELD';
        $this->arguments[] = $languageAttribute;

        return $this;
    }

    /**
     * Default score for documents in the index.
     *
     * @param  int   $defaultScore
     * @return $this
     */
    public function score(int $defaultScore): self
    {
        $this->arguments[] = 'SCORE';
        $this->arguments[] = $defaultScore;

        return $this;
    }

    /**
     * Document attribute that used as the document rank based on the user ranking.
     *
     * @param  string $scoreAttribute
     * @return $this
     */
    public function scoreField(string $scoreAttribute): self
    {
        $this->arguments[] = 'SCORE_FIELD';
        $this->arguments[] = $scoreAttribute;

        return $this;
    }

    /**
     * Document attribute that you use as a binary safe payload string.
     *
     * @param  string $payloadAttribute
     * @return $this
     */
    public function payloadField(string $payloadAttribute): self
    {
        $this->arguments[] = 'PAYLOAD_FIELD';
        $this->arguments[] = $payloadAttribute;

        return $this;
    }

    /**
     * Forces RediSearch to encode indexes as if there were more than 32 text attributes.
     *
     * @return $this
     */
    public function maxTextFields(): self
    {
        $this->arguments[] = 'MAXTEXTFIELDS';

        return $this;
    }

    /**
     * Does not store term offsets for documents.
     *
     * @return $this
     */
    public function noOffsets(): self
    {
        $this->arguments[] = 'NOOFFSETS';

        return $this;
    }

    /**
     * Creates a lightweight temporary index that expires after a specified period of inactivity, in seconds.
     *
     * @return $this
     */
    public function temporary(): self
    {
        $this->arguments[] = 'TEMPORARY';

        return $this;
    }

    /**
     * Conserves storage space and memory by disabling highlighting support.
     *
     * @return $this
     */
    public function noHl(): self
    {
        $this->arguments[] = 'NOHL';

        return $this;
    }

    /**
     * Does not store attribute bits for each term.
     *
     * @return $this
     */
    public function noFields(): self
    {
        $this->arguments[] = 'NOFIELDS';

        return $this;
    }

    /**
     * Avoids saving the term frequencies in the index.
     *
     * @return $this
     */
    public function noFreqs(): self
    {
        $this->arguments[] = 'NOFREQS';

        return $this;
    }

    /**
     * Sets the index with a custom stopword list, to be ignored during indexing and search time.
     *
     * @param  array $stopWords
     * @return $this
     */
    public function stopWords(array $stopWords): self
    {
        $this->arguments[] = 'STOPWORDS';
        $this->arguments[] = count($stopWords);
        $this->arguments = array_merge($this->arguments, $stopWords);

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
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
