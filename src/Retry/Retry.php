<?php

namespace Predis\Retry;

use Predis\Retry\Strategy\StrategyInterface;
use Predis\TimeoutException;

class Retry
{
    /**
     * @var StrategyInterface
     */
    protected $backoffStrategy;

    /**
     * @var int
     */
    protected $retries;

    /**
     * @var string[]
     */
    protected $retryableErrors;

    public function __construct(
        StrategyInterface $backoffStrategy,
        int $retries,
        array $retryableErrors = [
            TimeoutException::class
        ]
    ) {

    }
}
