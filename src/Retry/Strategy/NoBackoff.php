<?php

namespace Predis\Retry\Strategy;

/**
 * No backoff between retry
 */
class NoBackoff extends EqualBackoff
{
    public function __construct()
    {
        parent::__construct(0);
    }
}
