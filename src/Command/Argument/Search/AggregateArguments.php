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

class AggregateArguments extends CommonArguments
{
    /**
     * @var string[]
     */
    private $sortingEnum = [
        'asc' => 'ASC',
        'desc' => 'DESC',
    ];

    /**
     * Loads document attributes from the source document.
     *
     * @param  string ...$fields Could be just '*' to load all fields
     * @return $this
     */
    public function load(string ...$fields): self
    {
        $arguments = func_get_args();

        $this->arguments[] = 'LOAD';

        if ($arguments[0] === '*') {
            $this->arguments[] = '*';

            return $this;
        }

        $this->arguments[] = count($arguments);
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Loads document attributes from the source document.
     *
     * @param  string ...$properties
     * @return $this
     */
    public function groupBy(string ...$properties): self
    {
        $arguments = func_get_args();

        array_push($this->arguments, 'GROUPBY', count($arguments));
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Groups the results in the pipeline based on one or more properties.
     *
     * If you want to add alias property to your argument just add "true" value in arguments enumeration,
     * next value will be considered as alias to previous one.
     *
     * Example: 'argument', true, 'name' => 'argument' AS 'name'
     *
     * @param  string      $function
     * @param  string|bool ...$argument
     * @return $this
     */
    public function reduce(string $function, ...$argument): self
    {
        $arguments = func_get_args();
        $functionValue = array_shift($arguments);
        $argumentsCounter = 0;

        for ($i = 0, $iMax = count($arguments); $i < $iMax; $i++) {
            if (true === $arguments[$i]) {
                $arguments[$i] = 'AS';
                $i++;
                continue;
            }

            $argumentsCounter++;
        }

        array_push($this->arguments, 'REDUCE', $functionValue);
        $this->arguments = array_merge($this->arguments, [$argumentsCounter], $arguments);

        return $this;
    }

    /**
     * Sorts the pipeline up until the point of SORTBY, using a list of properties.
     *
     * @param  int    $max
     * @param  string ...$properties Enumeration of properties, including sorting direction (ASC, DESC)
     * @return $this
     */
    public function sortBy(int $max = 0, ...$properties): self
    {
        $arguments = func_get_args();
        $maxValue = array_shift($arguments);

        $this->arguments[] = 'SORTBY';
        $this->arguments = array_merge($this->arguments, [count($arguments)], $arguments);

        if ($maxValue !== 0) {
            array_push($this->arguments, 'MAX', $maxValue);
        }

        return $this;
    }

    /**
     * Applies a 1-to-1 transformation on one or more properties and either stores the result
     * as a new property down the pipeline or replaces any property using this transformation.
     *
     * @param  string $expression
     * @param  string $as
     * @return $this
     */
    public function apply(string $expression, string $as = ''): self
    {
        array_push($this->arguments, 'APPLY', $expression);

        if ($as !== '') {
            array_push($this->arguments, 'AS', $as);
        }

        return $this;
    }

    /**
     * Scan part of the results with a quicker alternative than LIMIT.
     *
     * @param  int   $readSize
     * @param  int   $idleTime
     * @return $this
     */
    public function withCursor(int $readSize = 0, int $idleTime = 0): self
    {
        $this->arguments[] = 'WITHCURSOR';

        if ($readSize !== 0) {
            array_push($this->arguments, 'COUNT', $readSize);
        }

        if ($idleTime !== 0) {
            array_push($this->arguments, 'MAXIDLE', $idleTime);
        }

        return $this;
    }
}
