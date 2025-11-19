<?php

namespace Predis;

use Exception;
use Predis\Retry\Retryable;
use Throwable;

class TimeoutException extends Exception implements Retryable
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct("Operation has been timed out", $code, $previous);
    }
}
