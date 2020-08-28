<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Collection\Iterator;

use PredisTestCase;

/**
 * @group realm-iterators
 */
class SortedSetKeyTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnMissingCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("'ZSCAN' is not supported by the current command factory.");

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')
            ->getMock();
        $commands
            ->expects($this->any())
            ->method('supports')
            ->willReturn(false);

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\ClientInterface')->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($commands);

        new SortedSetKey($client, 'key:zset');
    }

    /**
     * @group disconnected
     */
    public function testIterationWithNoResults(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->with('key:zset', 0, array())
            ->willReturn(
                array(0, array())
            );

        $iterator = new SortedSetKey($client, 'key:zset');

        $iterator->rewind();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @see https://github.com/predis/predis/issues/216
     * @group disconnected
     */
    public function testIterationWithIntegerMembers(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->with('key:zset', 0, array())
            ->willReturn(
                array(0, array(0 => 0, 101 => 1, 102 => 2))
            );

        $iterator = new SortedSetKey($client, 'key:zset');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(0, $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1, $iterator->current());
        $this->assertSame(101, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2, $iterator->current());
        $this->assertSame(102, $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationOnSingleFetch(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->with('key:zset', 0, array())
            ->willReturn(
                array(0, array('member:1st' => 1.0, 'member:2nd' => 2.0, 'member:3rd' => 3.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(3.0, $iterator->current());
        $this->assertSame('member:3rd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationOnMultipleFetches(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array()),
                array('key:zset', 2, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(2, array('member:1st' => 1.0, 'member:2nd' => 2.0)),
                array(0, array('member:3rd' => 3.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(3.0, $iterator->current());
        $this->assertSame('member:3rd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationOnMultipleFetchesAndHoleInFirstFetch(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array()),
                array('key:zset', 4, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(4, array()),
                array(0, array('member:1st' => 1.0, 'member:2nd' => 2.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationOnMultipleFetchesAndHoleInMidFetch(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(3))
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array()),
                array('key:zset', 2, array()),
                array('key:zset', 5, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(2, array('member:1st' => 1.0, 'member:2nd' => 2.0)),
                array(5, array()),
                array(0, array('member:3rd' => 3.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(3.0, $iterator->current());
        $this->assertSame('member:3rd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationWithOptionMatch(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array('MATCH' => 'member:*')),
                array('key:zset', 2, array('MATCH' => 'member:*'))
            )
            ->willReturnOnConsecutiveCalls(
                array(2, array('member:1st' => 1.0, 'member:2nd' => 2.0)),
                array(0, array())
            );

        $iterator = new SortedSetKey($client, 'key:zset', 'member:*');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationWithOptionMatchOnMultipleFetches(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array('MATCH' => 'member:*')),
                array('key:zset', 1, array('MATCH' => 'member:*'))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('member:1st' => 1.0)),
                array(0, array('member:2nd' => 2.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset', 'member:*');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationWithOptionCount(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array('COUNT' => 2))
            )
            ->willReturnOnConsecutiveCalls(
                array(0, array('member:1st' => 1.0, 'member:2nd' => 2.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset', null, 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationWithOptionCountOnMultipleFetches(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array('COUNT' => 1)),
                array('key:zset', 1, array('COUNT' => 1))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('member:1st' => 1.0)),
                array(0, array('member:2nd' => 2.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset', null, 1);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationWithOptionsMatchAndCount(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array('MATCH' => 'member:*', 'COUNT' => 2))
            )
            ->willReturnOnConsecutiveCalls(
                array(0, array('member:1st' => 1.0, 'member:2nd' => 2.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset', 'member:*', 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationWithOptionsMatchAndCountOnMultipleFetches(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                array('key:zset', 0, array('MATCH' => 'member:*', 'COUNT' => 1)),
                array('key:zset', 1, array('MATCH' => 'member:*', 'COUNT' => 1))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('member:1st' => 1.0)),
                array(0, array('member:2nd' => 2.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset', 'member:*', 1);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationRewindable(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('zscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->with('key:zset', 0, array())
            ->willReturn(
                array(0, array('member:1st' => 1.0, 'member:2nd' => 2.0))
            );

        $iterator = new SortedSetKey($client, 'key:zset');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1.0, $iterator->current());
        $this->assertSame('member:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(2.0, $iterator->current());
        $this->assertSame('member:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
