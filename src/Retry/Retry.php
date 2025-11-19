<?php

namespace Predis\Retry;

use Predis\Retry\Strategy\StrategyInterface;

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


    public function __construct(
        StrategyInterface $backoffStrategy,
        int $retries
    ) {
        $this->backoffStrategy = $backoffStrategy;
        $this->retries = $retries;
    }

    /**
     * Update the retry count
     *
     * @param int $retries
     * @return void
     */
    public function updateRetriesCount(int $retries): void
    {
        $this->retries = $retries;
    }

    /**
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * @param callable(): void $do
     * @param callable(Retryable): void|null $fail
     * @return void
     * @throws Retryable
     */
    public function callWithRetry(callable $do, callable $fail = null)
    {
        $failures = 0;

        while (true) {
            try {
                return $do();
            } catch (Retryable $e) {
                ++$failures;

                if ($fail !== null) {
                    $fail($e);
                }

                if ($this->retries >= 0 && $failures > $this->retries) {
                    throw $e;
                }

                $backoff = $this->backoffStrategy->compute($failures);
                if ($backoff > 0) {
                    usleep($backoff);
                }
            }
        }
    }
}
