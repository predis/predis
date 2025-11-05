<?php

namespace Predis\Command\Argument\Search\HybridSearch;

use Predis\Command\Argument\ArrayableArgument;

class Reducer implements ArrayableArgument
{
    public const REDUCE_COUNT = "COUNT";
    public const REDUCE_COUNT_DISTINCT = "COUNT_DISTINCT";
    public const REDUCE_COUNT_DISTINCTISH = "COUNT_DISTINCTISH";
    public const REDUCE_SUM = "SUM";
    public const REDUCE_MIN = "MIN";
    public const REDUCE_MAX = "MAX";
    public const REDUCE_AVG = "AVG";
    public const REDUCE_STDDEV = "STDDEV";
    public const REDUCE_QUANTILE = "QUANTILE";

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @param string $function One of the available functions. Check class constants.
     * @param array $arguments List of properties
     */
    public function __construct(string $function = self::REDUCE_COUNT, array $arguments = [])
    {
        array_push($this->arguments, $function, count($arguments), ...$arguments);
    }

    public function toArray(): array
    {
        return $this->arguments;
    }
}
