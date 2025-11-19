<?php

namespace Predis\Retry\Strategy;

use PHPUnit\Framework\TestCase;

class NoBackoffTest extends TestCase
{
    /**
     * @group disconnected
     * @return void
     */
    public function testCompute(): void
    {
        $backoff = new NoBackoff();
        $this->assertEquals(0, $backoff->compute(1));
    }
}
