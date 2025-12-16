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

namespace Predis\Retry;

use Predis\Connection\ConnectionException;
use Predis\Connection\Resource\Exception\StreamInitException;
use Predis\Retry\Strategy\RetryStrategyInterface;
use Predis\TimeoutException;
use Throwable;

class Retry
{
    /**
     * @var RetryStrategyInterface
     */
    protected $backoffStrategy;

    /**
     * @var int
     */
    protected $retries;

    /**
     * @var array
     */
    protected $catchableExceptions = [
        TimeoutException::class,
        ConnectionException::class,
        StreamInitException::class,
    ];

    /**
     * @param RetryStrategyInterface $backoffStrategy
     * @param int                    $retries
     * @param array|null             $catchableExceptions A list of exceptions classes that should be caught.
     *                                                    Overrides default list of the catchable exceptions.
     */
    public function __construct(
        RetryStrategyInterface $backoffStrategy,
        int $retries,
        ?array $catchableExceptions = null
    ) {
        $this->backoffStrategy = $backoffStrategy;
        $this->retries = $retries;

        if (null !== $catchableExceptions) {
            $this->catchableExceptions = $catchableExceptions;
        }
    }

    /**
     * Update the retry count.
     *
     * @param  int  $retries
     * @return void
     */
    public function updateRetriesCount(int $retries): void
    {
        $this->retries = $retries;
    }

    /**
     * Extend catchable exceptions list.
     *
     * @param  array $catchableExceptions
     * @return void
     */
    public function updateCatchableExceptions(array $catchableExceptions): void
    {
        $this->catchableExceptions = array_merge($this->catchableExceptions, $catchableExceptions);
    }

    /**
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * @return RetryStrategyInterface
     */
    public function getStrategy(): RetryStrategyInterface
    {
        return $this->backoffStrategy;
    }

    /**
     * @param  callable(): mixed              $do
     * @param  callable(Throwable): void|null $fail
     * @return mixed
     * @throws Throwable
     */
    public function callWithRetry(callable $do, ?callable $fail = null)
    {
        $failures = 0;

        while (true) {
            try {
                return $do();
            } catch (Throwable $e) {
                if (null !== $this->catchableExceptions) {
                    $isCatchable = false;
                    foreach ($this->catchableExceptions as $catchableException) {
                        if ($e instanceof $catchableException) {
                            $isCatchable = true;
                        }
                    }

                    if (!$isCatchable) {
                        throw $e;
                    }
                }

                $backoff = $this->backoffStrategy->compute($failures);
                ++$failures;

                if ($this->retries >= 0 && $failures > $this->retries) {
                    throw $e;
                }

                if ($fail !== null) {
                    $fail($e);
                }

                if ($backoff > 0) {
                    usleep($backoff);
                }
            }
        }
    }
}
