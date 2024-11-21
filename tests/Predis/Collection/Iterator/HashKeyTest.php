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
class HashKeyTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnMissingCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("'HSCAN' is not supported by the current command factory.");

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
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

        new HashKey($client, 'key:hash');
    }

    /**
     * @group disconnected
     */
    public function testIterationWithNoResults(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('hscan')
            ->with('key:hash', 0, [])
            ->willReturn(
                [0, [],
                ]);

        $iterator = new HashKey($client, 'key:hash');

        $iterator->rewind();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @see https://github.com/predis/predis/pull/330
     * @see https://github.com/predis/predis/issues/331
     * @group disconnected
     */
    public function testIterationWithIntegerFields(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('hscan')
            ->with('key:hash', 0, [])
            ->willReturn(
                [0, [1 => 'a', 2 => 'b', 3 => 100, 'foo' => 'bar']]
            );

        $iterator = new HashKey($client, 'key:hash');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('a', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('b', $iterator->current());
        $this->assertSame(2, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(100, $iterator->current());
        $this->assertSame(3, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('bar', $iterator->current());
        $this->assertSame('foo', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('hscan')
            ->with('key:hash', 0, [])
            ->willReturn(
                [0, ['field:1st' => 'value:1st', 'field:2nd' => 'value:2nd', 'field:3rd' => 'value:3rd']]
            );

        $iterator = new HashKey($client, 'key:hash');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:3rd', $iterator->current());
        $this->assertSame('field:3rd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, []],
                ['key:hash', 2, []]
            )
            ->willReturnOnConsecutiveCalls(
                [2, ['field:1st' => 'value:1st', 'field:2nd' => 'value:2nd']],
                [0, ['field:3rd' => 'value:3rd']]
            );

        $iterator = new HashKey($client, 'key:hash');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:3rd', $iterator->current());
        $this->assertSame('field:3rd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, []],
                ['key:hash', 4, []]
            )
            ->willReturnOnConsecutiveCalls(
                [4, []],
                [0, ['field:1st' => 'value:1st', 'field:2nd' => 'value:2nd']]
            );

        $iterator = new HashKey($client, 'key:hash');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(3))
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, []],
                ['key:hash', 2, []],
                ['key:hash', 5, []]
            )
            ->willReturnOnConsecutiveCalls(
                [2, ['field:1st' => 'value:1st', 'field:2nd' => 'value:2nd']],
                [5, []],
                [0, ['field:3rd' => 'value:3rd']]
            );

        $iterator = new HashKey($client, 'key:hash');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:3rd', $iterator->current());
        $this->assertSame('field:3rd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, ['MATCH' => 'field:*']],
                ['key:hash', 2, ['MATCH' => 'field:*']]
            )
            ->willReturn(
                [2, ['field:1st' => 'value:1st', 'field:2nd' => 'value:2nd']],
                [0, []]
            );

        $iterator = new HashKey($client, 'key:hash', 'field:*');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, ['MATCH' => 'field:*']],
                ['key:hash', 1, ['MATCH' => 'field:*']]
            )
            ->willReturn(
                [1, ['field:1st' => 'value:1st']],
                [0, ['field:2nd' => 'value:2nd']]
            );

        $iterator = new HashKey($client, 'key:hash', 'field:*');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, ['COUNT' => 2]]
            )
            ->willReturn(
                [0, ['field:1st' => 'value:1st', 'field:2nd' => 'value:2nd']]
            );

        $iterator = new HashKey($client, 'key:hash', null, 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, ['COUNT' => 1]],
                ['key:hash', 1, ['COUNT' => 1]]
            )
            ->willReturnOnConsecutiveCalls(
                [1, ['field:1st' => 'value:1st']],
                [0, ['field:2nd' => 'value:2nd']]
            );

        $iterator = new HashKey($client, 'key:hash', null, 1);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, ['MATCH' => 'field:*', 'COUNT' => 2]]
            )
            ->willReturnOnConsecutiveCalls(
                [0, ['field:1st' => 'value:1st', 'field:2nd' => 'value:2nd']]
            );

        $iterator = new HashKey($client, 'key:hash', 'field:*', 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('hscan')
            ->withConsecutive(
                ['key:hash', 0, ['MATCH' => 'field:*', 'COUNT' => 1]],
                ['key:hash', 1, ['MATCH' => 'field:*', 'COUNT' => 1]]
            )
            ->willReturnOnConsecutiveCalls(
                [1, ['field:1st' => 'value:1st']],
                [0, ['field:2nd' => 'value:2nd']]
            );

        $iterator = new HashKey($client, 'key:hash', 'field:*', 1);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

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
            ->addMethods(['hscan'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('hscan')
            ->with('key:hash', 0, [])
            ->willReturn(
                [0, ['field:1st' => 'value:1st', 'field:2nd' => 'value:2nd']]
            );

        $iterator = new HashKey($client, 'key:hash');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:1st', $iterator->current());
        $this->assertSame('field:1st', $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('value:2nd', $iterator->current());
        $this->assertSame('field:2nd', $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
