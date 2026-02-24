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

namespace Predis\Cluster;

use PredisTestCase;

class SlotRangeTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testMaxSlotsEquals16383()
    {
        $this->assertEquals(SlotRange::MAX_SLOTS, 16383);
    }

    /**
     * @group disconnected
     */
    public function testConstructorThrowExceptionOnInvalidSlotRange(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->expectExceptionMessage('Invalid slot range 600-300 for `c1`');

        new SlotRange(600, 300, 'c1');
    }

    /**
     * @group disconnected
     */
    public function testIsValidRangeReturnsTrueOnValidSlotRange()
    {
        $this->assertTrue(SlotRange::isValidRange(0, SlotRange::MAX_SLOTS));
        $this->assertTrue(SlotRange::isValidRange(2000, 2999));
        $this->assertTrue(SlotRange::isValidRange(3000, 3000));
    }

    /**
     * @group disconnected
     */
    public function testIsValidRangeReturnsFalseOnInvalidSlotRange()
    {
        $this->assertFalse(SlotRange::isValidRange(0, 16384));
        $this->assertFalse(SlotRange::isValidRange(-1, 16383));
        $this->assertFalse(SlotRange::isValidRange(-1, 16384));
        $this->assertFalse(SlotRange::isValidRange(2999, 2000));
    }

    /**
     * @group disconnected
     */
    public function testPropertySetters()
    {
        $range = new SlotRange(2000, 5000, 'c1');

        $this->assertEquals(2000, $range->getStart());
        $this->assertEquals(5000, $range->getEnd());
        $this->assertEquals('c1', $range->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testHasSlot()
    {
        $range = new SlotRange(2000, 4000, 'c1');

        $this->assertTrue($range->hasSlot(2000));
        $this->assertTrue($range->hasSlot(4000));
        $this->assertTrue($range->hasSlot(3000));

        $this->assertFalse($range->hasSlot(1000));
        $this->assertFalse($range->hasSlot(5000));
    }

    /**
     * @group disconnected
     */
    public function testToArray()
    {
        $range = new SlotRange(2000, 2005, 'c1');
        $array = $range->toArray();
        $this->assertSame([
            2000 => 'c1',
            2001 => 'c1',
            2002 => 'c1',
            2003 => 'c1',
            2004 => 'c1',
            2005 => 'c1',
        ], $array);
    }

    /**
     * @group disconnected
     */
    public function testCount()
    {
        $range = new SlotRange(2000, 3000, 'c1');
        $this->assertEquals(1001, $range->count());
        $this->assertEquals(1001, count($range));
    }

    /**
     * @group disconnected
     */
    public function testHasIntersectionWith()
    {
        $original = new SlotRange(2000, 3000, 'c1');

        $this->assertFalse($original->hasIntersectionWith(new SlotRange(1000, 1500, 'c1')));
        $this->assertFalse($original->hasIntersectionWith(new SlotRange(3001, 5000, 'c1')));
        $this->assertFalse($original->hasIntersectionWith(new SlotRange(1999, 1999, 'c1')));
        $this->assertFalse($original->hasIntersectionWith(new SlotRange(3001, 3001, 'c1')));

        $this->assertTrue($original->hasIntersectionWith(new SlotRange(1500, 6000, 'c1')));
        $this->assertTrue($original->hasIntersectionWith(new SlotRange(2500, 2999, 'c1')));
        $this->assertTrue($original->hasIntersectionWith(new SlotRange(2000, 2999, 'c1')));
        $this->assertTrue($original->hasIntersectionWith(new SlotRange(2500, 3000, 'c1')));
        $this->assertTrue($original->hasIntersectionWith(new SlotRange(1500, 2000, 'c1')));
        $this->assertTrue($original->hasIntersectionWith(new SlotRange(3000, 3500, 'c1')));
        $this->assertTrue($original->hasIntersectionWith(new SlotRange(2000, 2000, 'c1')));
        $this->assertTrue($original->hasIntersectionWith(new SlotRange(3000, 3000, 'c1')));
    }
}
