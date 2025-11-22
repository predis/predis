<?php

namespace Predis\Retry\Strategy;

use PHPUnit\Framework\TestCase;

class EqualBackoffTest extends TestCase
{
    /**
     * @group disconnected
     * @return void
     */
    public function testCompute(): void
    {
        $backoff = new EqualBackoff(1);
        $this->assertEquals(1, $backoff->compute(1));
    }
}
