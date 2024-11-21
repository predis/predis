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

use InvalidArgumentException;

class SearchArguments extends CommonArguments
{
    /**
     * @var string[]
     */
    private $sortingEnum = [
        'asc' => 'ASC',
        'desc' => 'DESC',
    ];

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
}
