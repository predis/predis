<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->with('key:zset', 0, [])
            ->willReturn(
                [0, []]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->with('key:zset', 0, [])
            ->willReturn(
                [0, [0 => 0, 101 => 1, 102 => 2]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->with('key:zset', 0, [])
            ->willReturn(
                [0, ['member:1st' => 1.0, 'member:2nd' => 2.0, 'member:3rd' => 3.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, []],
                ['key:zset', 2, []]
            )
            ->willReturnOnConsecutiveCalls(
                [2, ['member:1st' => 1.0, 'member:2nd' => 2.0]],
                [0, ['member:3rd' => 3.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, []],
                ['key:zset', 4, []]
            )
            ->willReturnOnConsecutiveCalls(
                [4, []],
                [0, ['member:1st' => 1.0, 'member:2nd' => 2.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(3))
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, []],
                ['key:zset', 2, []],
                ['key:zset', 5, []]
            )
            ->willReturnOnConsecutiveCalls(
                [2, ['member:1st' => 1.0, 'member:2nd' => 2.0]],
                [5, []],
                [0, ['member:3rd' => 3.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, ['MATCH' => 'member:*']],
                ['key:zset', 2, ['MATCH' => 'member:*']]
            )
            ->willReturnOnConsecutiveCalls(
                [2, ['member:1st' => 1.0, 'member:2nd' => 2.0]],
                [0, []]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, ['MATCH' => 'member:*']],
                ['key:zset', 1, ['MATCH' => 'member:*']]
            )
            ->willReturnOnConsecutiveCalls(
                [1, ['member:1st' => 1.0]],
                [0, ['member:2nd' => 2.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, ['COUNT' => 2]]
            )
            ->willReturnOnConsecutiveCalls(
                [0, ['member:1st' => 1.0, 'member:2nd' => 2.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, ['COUNT' => 1]],
                ['key:zset', 1, ['COUNT' => 1]]
            )
            ->willReturnOnConsecutiveCalls(
                [1, ['member:1st' => 1.0]],
                [0, ['member:2nd' => 2.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, ['MATCH' => 'member:*', 'COUNT' => 2]]
            )
            ->willReturnOnConsecutiveCalls(
                [0, ['member:1st' => 1.0, 'member:2nd' => 2.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->withConsecutive(
                ['key:zset', 0, ['MATCH' => 'member:*', 'COUNT' => 1]],
                ['key:zset', 1, ['MATCH' => 'member:*', 'COUNT' => 1]]
            )
            ->willReturnOnConsecutiveCalls(
                [1, ['member:1st' => 1.0]],
                [0, ['member:2nd' => 2.0]]
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
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['zscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('zscan')
            ->with('key:zset', 0, [])
            ->willReturn(
                [0, ['member:1st' => 1.0, 'member:2nd' => 2.0]]
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
