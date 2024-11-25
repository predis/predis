<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
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
    public function testPropertySettersAndGetters()
    {
        $range = new SlotRange(2000, 5000, 'c1');

        $this->assertEquals(2000, $range->getStart());
        $this->assertEquals(5000, $range->getEnd());
        $this->assertEquals('c1', $range->getConnection());

        $range->setStart(3000);
        $this->assertEquals(3000, $range->getStart());

        $range->setEnd(8000);
        $this->assertEquals(8000, $range->getEnd());

        $range->setConnection('c2');
        $this->assertEquals('c2', $range->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testSetStartThrowExceptionOnInvalidSlotRange(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->expectExceptionMessage('Invalid slot range start, range: 5000-3000');

        $range = new SlotRange(1000, 3000, 'c1');

        $range->setStart(5000);
    }

    /**
     * @group disconnected
     */
    public function testSetEndThrowExceptionOnInvalidSlotRange(): void
    {
        $this->expectException('OutOfBoundsException');
        $this->expectExceptionMessage('Invalid slot range end, range: 1000-100');

        $range = new SlotRange(1000, 3000, 'c1');

        $range->setEnd(100);
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
    public function testCopy()
    {
        $range = new SlotRange(1000, 3000, 'c1');

        $copy = $range->copy();

        $this->assertInstanceOf(SlotRange::class, $copy);
        $this->assertEquals(1000, $copy->getStart());
        $this->assertEquals(3000, $copy->getEnd());
        $this->assertEquals('c1', $copy->getConnection());

        $this->assertEquals($range, $copy);
        $this->assertNotSame($range, $copy);

        $range->setStart(1500);
        $this->assertNotEquals($range, $copy);
        $this->assertEquals(1500, $range->getStart());
        $this->assertEquals(1000, $copy->getStart());
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
