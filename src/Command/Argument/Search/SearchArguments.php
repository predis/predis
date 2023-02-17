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

class SearchArguments implements ArrayableArgument
{
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var string[]
     */
    private $supportedDataTypesEnum = [
        'hash' => 'HASH',
        'json' => 'JSON',
    ];

    /**
     * @var string[]
     */
    private $sortingEnum = [
        'asc' => 'ASC',
        'desc' => 'DESC',
    ];

    /**
     * Specify data type for given index. To index JSON you must have the RedisJSON module to be installed.
     *
     * @param  string $modifier
     * @return $this
     */
    public function on(string $modifier): self
    {
        if (in_array(strtoupper($modifier), $this->supportedDataTypesEnum)) {
            $this->arguments[] = 'ON';
            $this->arguments[] = $this->supportedDataTypesEnum[strtolower($modifier)];

            return $this;
        }

        $enumValues = implode(', ', array_values($this->supportedDataTypesEnum));
        throw new InvalidArgumentException("Wrong modifier value given. Currently supports: {$enumValues}");
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
     * @param  float $defaultScore
     * @return $this
     */
    public function score(float $defaultScore): self
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
     * Returns the document ids and not the content.
     *
     * @return $this
     */
    public function noContent(): self
    {
        $this->arguments[] = 'NOCONTENT';

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
     * Returns the value of the sorting key, right after the id and score and/or payload, if requested.
     *
     * @return $this
     */
    public function withSortKeys(): self
    {
        $this->arguments[] = 'WITHSORTKEYS';

        return $this;
    }

    /**
     * Limits results to those having numeric values ranging between min and max,
     * if numeric_attribute is defined as a numeric attribute in FT.CREATE.
     * Min and max follow ZRANGE syntax, and can be -inf, +inf, and use( for exclusive ranges.
     * Multiple numeric filters for different attributes are supported in one query.
     *
     * @param  array ...$filter Should contain: numeric_field, min and max. Example: ['numeric_field', 1, 10]
     * @return $this
     */
    public function searchFilter(array ...$filter): self
    {
        $arguments = func_get_args();

        foreach ($arguments as $argument) {
            array_push($this->arguments, 'FILTER', ...$argument);
        }

        return $this;
    }

    /**
     * Filter the results to a given radius from lon and lat. Radius is given as a number and units.
     *
     * @param  array ...$filter Should contain: geo_field, lon, lat, radius, unit. Example: ['geo_field', 34.1231, 35.1231, 300, km]
     * @return $this
     */
    public function geoFilter(array ...$filter): self
    {
        $arguments = func_get_args();

        foreach ($arguments as $argument) {
            array_push($this->arguments, 'GEOFILTER', ...$argument);
        }

        return $this;
    }

    /**
     * Limits the result to a given set of keys specified in the list.
     *
     * @param  array $keys
     * @return $this
     */
    public function inKeys(array $keys): self
    {
        $this->arguments[] = 'INKEYS';
        $this->arguments[] = count($keys);
        $this->arguments = array_merge($this->arguments, $keys);

        return $this;
    }

    /**
     * Filters the results to those appearing only in specific attributes of the document, like title or URL.
     *
     * @param  array $fields
     * @return $this
     */
    public function inFields(array $fields): self
    {
        $this->arguments[] = 'INFIELDS';
        $this->arguments[] = count($fields);
        $this->arguments = array_merge($this->arguments, $fields);

        return $this;
    }

    /**
     * Limits the attributes returned from the document.
     * Num is the number of attributes following the keyword.
     * If num is 0, it acts like NOCONTENT.
     * Identifier is either an attribute name (for hashes and JSON) or a JSON Path expression (for JSON).
     * Property is an optional name used in the result. If not provided, the identifier is used in the result.
     *
     * If you want to add alias property to your identifier just add "true" value in identifier enumeration,
     * next value will be considered as alias to previous one.
     *
     * Example: 'identifier', true, 'property' => 'identifier' AS 'property'
     *
     * @param  int         $count
     * @param  string|bool ...$identifier
     * @return $this
     */
    public function addReturn(int $count, ...$identifier): self
    {
        $arguments = func_get_args();

        $this->arguments[] = 'RETURN';

        for ($i = 1, $iMax = count($arguments); $i < $iMax; $i++) {
            if (true === $arguments[$i]) {
                $arguments[$i] = 'AS';
            }
        }

        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Returns only the sections of the attribute that contain the matched text.
     *
     * @param  array  $fields
     * @param  int    $frags
     * @param  int    $len
     * @param  string $separator
     * @return $this
     */
    public function summarize(array $fields = [], int $frags = 0, int $len = 0, string $separator = ''): self
    {
        $this->arguments[] = 'SUMMARIZE';

        if (!empty($fields)) {
            $this->arguments[] = 'FIELDS';
            $this->arguments[] = count($fields);
            $this->arguments = array_merge($this->arguments, $fields);
        }

        if ($frags !== 0) {
            $this->arguments[] = 'FRAGS';
            $this->arguments[] = $frags;
        }

        if ($len !== 0) {
            $this->arguments[] = 'LEN';
            $this->arguments[] = $len;
        }

        if ($separator !== '') {
            $this->arguments[] = 'SEPARATOR';
            $this->arguments[] = $separator;
        }

        return $this;
    }

    /**
     * Formats occurrences of matched text.
     *
     * @param  array  $fields
     * @param  string $openTag
     * @param  string $closeTag
     * @return $this
     */
    public function highlight(array $fields = [], string $openTag = '', string $closeTag = ''): self
    {
        $this->arguments[] = 'HIGHLIGHT';

        if (!empty($fields)) {
            $this->arguments[] = 'FIELDS';
            $this->arguments[] = count($fields);
            $this->arguments = array_merge($this->arguments, $fields);
        }

        if ($openTag !== '' && $closeTag !== '') {
            array_push($this->arguments, 'TAGS', $openTag, $closeTag);
        }

        return $this;
    }

    /**
     * Allows a maximum of N intervening number of unmatched offsets between phrase terms.
     * In other words, the slop for exact phrases is 0.
     *
     * @param  int   $slop
     * @return $this
     */
    public function slop(int $slop): self
    {
        $this->arguments[] = 'SLOP';
        $this->arguments[] = $slop;

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
     * Puts the query terms in the same order in the document as in the query, regardless of the offsets between them.
     * Typically used in conjunction with SLOP.
     *
     * @return $this
     */
    public function inOrder(): self
    {
        $this->arguments[] = 'INORDER';

        return $this;
    }

    /**
     * Uses a custom query expander instead of the stemmer.
     *
     * @param  string $expander
     * @return $this
     */
    public function expander(string $expander): self
    {
        $this->arguments[] = 'EXPANDER';
        $this->arguments[] = $expander;

        return $this;
    }

    /**
     * Uses a custom scoring function you define.
     *
     * @param  string $scorer
     * @return $this
     */
    public function scorer(string $scorer): self
    {
        $this->arguments[] = 'SCORER';
        $this->arguments[] = $scorer;

        return $this;
    }

    /**
     * Returns a textual description of how the scores were calculated.
     * Using this options requires the WITHSCORES option.
     *
     * @return $this
     */
    public function explainScore(): self
    {
        $this->arguments[] = 'EXPLAINSCORE';

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
     * Orders the results by the value of this attribute.
     * This applies to both text and numeric attributes.
     * Attributes needed for SORTBY should be declared as SORTABLE in the index, in order to be available with very low latency.
     * Note that this adds memory overhead.
     *
     * @param  string $sortAttribute
     * @param  string $orderBy
     * @return $this
     */
    public function sortBy(string $sortAttribute, string $orderBy = 'asc'): self
    {
        $this->arguments[] = 'SORTBY';
        $this->arguments[] = $sortAttribute;

        if (in_array(strtoupper($orderBy), $this->sortingEnum)) {
            $this->arguments[] = $this->sortingEnum[strtolower($orderBy)];
        } else {
            $enumValues = implode(', ', array_values($this->sortingEnum));
            throw new InvalidArgumentException("Wrong order direction value given. Currently supports: {$enumValues}");
        }

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
