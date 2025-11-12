<?php

namespace Predis;

use Exception;

class TimeoutException extends Exception
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct("Operation has been timed out", $code, $previous);
    }
}
