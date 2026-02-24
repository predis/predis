<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
