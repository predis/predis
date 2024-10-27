<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use PredisTestCase;

class ReadonlyOperationDetectorTest extends PredisTestCase
{
    private $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = new ReadonlyOperationDetector();
    }

    /**
     * @group disconnected
     */
    public function testDetectForAnyCommand(): void
    {
        $getCommand = new RawCommand('GET', ['key']);
        $this->assertTrue($this->detector->detect($getCommand));

        $setCommand = new RawCommand('SET', ['key', 'value']);
        $this->assertFalse($this->detector->detect($setCommand));
    }

    /**
     * @group disconnected
     */
    public function testDetectForBitfieldCommand(): void
    {
        $bitfieldCommandReadonly = new RawCommand('BITFIELD', ['mybitset', 'GET', 'u1', '2', 'GET', 'u8', '1']);
        $this->assertTrue($this->detector->detect($bitfieldCommandReadonly));

        $bitfieldCommand = new RawCommand('BITFIELD', ['mybitset', 'INCRBY', 'i5', '100', '1', 'GET', 'u4', '0']);

        $this->assertFalse($this->detector->detect($bitfieldCommand));
    }

    /**
     * @group disconnected
     */
    public function testDetectForGeoradiusCommand(): void
    {
        $georadiusCommandReadonly = new RawCommand('GEORADIUS', ['cities', '-74.0060', '40.7128', '100', 'km', 'WITHCOORD', 'WITHDIST']);
        $this->assertTrue($this->detector->detect($georadiusCommandReadonly));

        $georadiusCommand = new RawCommand('GEORADIUS', ['cities', '-74.0060', '40.7128', '100', 'km', 'WITHCOORD', 'WITHDIST', 'STORE', 'nearby_cities']);
        $this->assertFalse($this->detector->detect($georadiusCommand));
    }
}
