<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster\Hash;

use PredisTestCase;

/**
 * @group ext-phpiredis
 * @requires extension phpiredis
 * @requires function phpiredis_utils_crc16
 */
class PhpiredisCRC16Test extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testHashGeneration(): void
    {
        $crc16 = new PhpiredisCRC16();

        $this->assertSame(58359, $crc16->hash('key:000'));
        $this->assertSame(62422, $crc16->hash('key:001'));
        $this->assertSame(50101, $crc16->hash('key:002'));
        $this->assertSame(54164, $crc16->hash('key:003'));
        $this->assertSame(41843, $crc16->hash('key:004'));
        $this->assertSame(45906, $crc16->hash('key:005'));
        $this->assertSame(33585, $crc16->hash('key:006'));
        $this->assertSame(37648, $crc16->hash('key:007'));
        $this->assertSame(25343, $crc16->hash('key:008'));
        $this->assertSame(29406, $crc16->hash('key:009'));
    }
}
