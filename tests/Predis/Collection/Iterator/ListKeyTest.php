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
class ListKeyTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnMissingCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("'LRANGE' is not supported by the current command factory.");

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->any())
            ->method('supports')
            ->willReturn(false);

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['lrange'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($commands);

        new ListKey($client, 'key:list');
    }

    /**
     * @group disconnected
     */
    public function testIterationWithNoResults(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['lrange'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('lrange')
            ->with('key:list', 0, 9)
            ->willReturn(
                []
            );

        $iterator = new ListKey($client, 'key:list');

        $iterator->rewind();
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
            ->addMethods(['lrange'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('lrange')
            ->with('key:list', 0, 9)
            ->willReturn(
                ['item:1', 'item:2', 'item:3']
            );

        $iterator = new ListKey($client, 'key:list');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:1', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:2', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:3', $iterator->current());
        $this->assertSame(2, $iterator->key());

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
            ->addMethods(['lrange'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('lrange')
            ->withConsecutive(
                ['key:list', 0, 9],
                ['key:list', 10, 19]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'item:1', 'item:2', 'item:3', 'item:4', 'item:5',
                    'item:6', 'item:7', 'item:8', 'item:9', 'item:10',
                ],
                ['item:11', 'item:12']
            );

        $iterator = new ListKey($client, 'key:list');

        for ($i = 1, $iterator->rewind(); $i <= 12; $i++, $iterator->next()) {
            $this->assertTrue($iterator->valid());
            $this->assertSame("item:$i", $iterator->current());
            $this->assertSame($i - 1, $iterator->key());
        }

        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnConstructorWithNonIntegerCountParameter(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The $count argument must be a positive integer');

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['getCommandFactory'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());

        new ListKey($client, 'key:list', 'wrong');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnConstructorWithNegativeCountParameter(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The $count argument must be a positive integer');

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['getCommandFactory'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());

        new ListKey($client, 'key:list', 'wrong');
    }

    /**
     * @group disconnected
     */
    public function testIterationWithCountParameter(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['lrange'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('lrange')
            ->with('key:list', 0, 4)
            ->willReturn(
                ['item:1', 'item:2']
            );

        $iterator = new ListKey($client, 'key:list', 5);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:1', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:2', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }

    /**
     * @group disconnected
     */
    public function testIterationWithCountParameterOnMultipleFetches(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['getCommandFactory'])
            ->addMethods(['lrange'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('lrange')
            ->withConsecutive(
                ['key:list', 0, 1],
                ['key:list', 2, 3]
            )
            ->willReturnOnConsecutiveCalls(
                ['item:1', 'item:2'],
                ['item:3']
            );

        $iterator = new ListKey($client, 'key:list', 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:1', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:2', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:3', $iterator->current());
        $this->assertSame(2, $iterator->key());

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
            ->addMethods(['lrange'])
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('lrange')
            ->with('key:list', 0, 9)
            ->willReturn(
                ['item:1', 'item:2']
            );

        $iterator = new ListKey($client, 'key:list');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:1', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('item:1', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1, $iterator->key());
        $this->assertSame('item:2', $iterator->current());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
