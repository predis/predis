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
class KeyspaceTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnMissingCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("'SCAN' is not supported by the current command factory.");

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

        new Keyspace($client);
    }

    /**
     * @group disconnected
     */
    public function testIterationWithNoResults(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('scan')
            ->with(0, array())
            ->willReturn(
                array(0, array())
            );

        $iterator = new Keyspace($client);

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
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('scan')
            ->with(0, array())
            ->willReturn(
                array(0, array('key:1st', 'key:2nd', 'key:3rd'))
            );

        $iterator = new Keyspace($client);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:3rd', $iterator->current());
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
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('scan')
            ->withConsecutive(
                array(0, array()),
                array(2, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(2, array('key:1st', 'key:2nd')),
                array(0, array('key:3rd'))
            );

        $iterator = new Keyspace($client);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:3rd', $iterator->current());
        $this->assertSame(2, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('scan')
            ->withConsecutive(
                array(0, array()),
                array(4, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(4, array()),
                array(0, array('key:1st', 'key:2nd'))
            );

        $iterator = new Keyspace($client);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(3))
            ->method('scan')
            ->withConsecutive(
                array(0, array()),
                array(2, array()),
                array(5, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(2, array('key:1st', 'key:2nd')),
                array(5, array()),
                array(0, array('key:3rd'))
            );

        $iterator = new Keyspace($client);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:3rd', $iterator->current());
        $this->assertSame(2, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('scan')
            ->withConsecutive(
                array(0, array('MATCH' => 'key:*'))
            )
            ->willReturnOnConsecutiveCalls(
                array(0, array('key:1st', 'key:2nd'))
            );

        $iterator = new Keyspace($client, 'key:*');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('scan')
            ->withConsecutive(
                array(0, array('MATCH' => 'key:*')),
                array(1, array('MATCH' => 'key:*'))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('key:1st')),
                array(0, array('key:2nd'))
            );

        $iterator = new Keyspace($client, 'key:*');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('scan')
            ->withConsecutive(
                array(0, array('COUNT' => 2))
            )
            ->willReturnOnConsecutiveCalls(
                array(0, array('key:1st', 'key:2nd'))
            );

        $iterator = new Keyspace($client, null, 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());


        $client
            ->expects($this->exactly(2))
            ->method('scan')
            ->withConsecutive(
                array(0, array('COUNT' => 1)),
                array(1, array('COUNT' => 1))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('key:1st')),
                array(0, array('key:2nd'))
            );

        $iterator = new Keyspace($client, null, 1);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('scan')
            ->withConsecutive(
                array(0, array('MATCH' => 'key:*', 'COUNT' => 2))
            )
            ->willReturnOnConsecutiveCalls(
                array(0, array('key:1st', 'key:2nd'))
            );

        $iterator = new Keyspace($client, 'key:*', 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());


        $client
            ->expects($this->exactly(2))
            ->method('scan')
            ->withConsecutive(
                array(0, array('MATCH' => 'key:*', 'COUNT' => 1)),
                array(1, array('MATCH' => 'key:*', 'COUNT' => 1))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('key:1st')),
                array(0, array('key:2nd'))
            );

        $iterator = new Keyspace($client, 'key:*', 1);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

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
            ->addMethods(array('scan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('scan')
            ->with(0, array())
            ->willReturn(
                array(0, array('key:1st', 'key:2nd'))
            );

        $iterator = new Keyspace($client);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('key:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame(1, $iterator->key());
        $this->assertSame('key:2nd', $iterator->current());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
