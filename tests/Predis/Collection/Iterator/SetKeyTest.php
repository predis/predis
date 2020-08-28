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
class SetKeyTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnMissingCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("'SSCAN' is not supported by the current command factory.");

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

        new SetKey($client, 'key:set');
    }

    /**
     * @group disconnected
     */
    public function testIterationWithNoResults(): void
    {
        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('getCommandFactory'))
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('sscan')
            ->with('key:set', 0, array())
            ->willReturn(
                array(0, array())
            );

        $iterator = new SetKey($client, 'key:set');

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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('sscan')
            ->with('key:set', 0, array())
            ->willReturn(
                array(0, array('member:1st', 'member:2nd', 'member:3rd'))
            );

        $iterator = new SetKey($client, 'key:set');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:3rd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array()),
                array('key:set', 2, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(2, array('member:1st', 'member:2nd')),
                array(0, array('member:3rd'))
            );

        $iterator = new SetKey($client, 'key:set');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:3rd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array()),
                array('key:set', 4, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(4, array()),
                array(0, array('member:1st', 'member:2nd'))
            );

        $iterator = new SetKey($client, 'key:set');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(3))
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array()),
                array('key:set', 2, array()),
                array('key:set', 5, array())
            )
            ->willReturnOnConsecutiveCalls(
                array(2, array('member:1st', 'member:2nd')),
                array(5, array()),
                array(0, array('member:3rd'))
            );

        $iterator = new SetKey($client, 'key:set');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:3rd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array('MATCH' => 'member:*'))
            )
            ->willReturnOnConsecutiveCalls(
                array(0, array('member:1st', 'member:2nd'))
            );

        $iterator = new SetKey($client, 'key:set', 'member:*');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array('MATCH' => 'member:*')),
                array('key:set', 1, array('MATCH' => 'member:*'))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('member:1st')),
                array(0, array('member:2nd'))
            );

        $iterator = new SetKey($client, 'key:set', 'member:*');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array('COUNT' => 2))
            )
            ->willReturnOnConsecutiveCalls(
                array(0, array('member:1st', 'member:2nd'))
            );

        $iterator = new SetKey($client, 'key:set', null, 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array('COUNT' => 1)),
                array('key:set', 1, array('COUNT' => 1))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('member:1st')),
                array(0, array('member:2nd'))
            );

        $iterator = new SetKey($client, 'key:set', null, 1);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->once())
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array('MATCH' => 'member:*', 'COUNT' => 2))
            )
            ->willReturnOnConsecutiveCalls(
                array(0, array('member:1st', 'member:2nd'))
            );

        $iterator = new SetKey($client, 'key:set', 'member:*', 2);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('sscan')
            ->withConsecutive(
                array('key:set', 0, array('MATCH' => 'member:*', 'COUNT' => 1)),
                array('key:set', 1, array('MATCH' => 'member:*', 'COUNT' => 1))
            )
            ->willReturnOnConsecutiveCalls(
                array(1, array('member:1st')),
                array(0, array('member:2nd'))
            );

        $iterator = new SetKey($client, 'key:set', 'member:*', 1);

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
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
            ->addMethods(array('sscan'))
            ->getMock();
        $client
            ->expects($this->any())
            ->method('getCommandFactory')
            ->willReturn($this->getCommandFactory());
        $client
            ->expects($this->exactly(2))
            ->method('sscan')
            ->with('key:set', 0, array())
            ->willReturn(
                array(0, array('member:1st', 'member:2nd'))
            );

        $iterator = new SetKey($client, 'key:set');

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:1st', $iterator->current());
        $this->assertSame(0, $iterator->key());

        $iterator->next();
        $this->assertTrue($iterator->valid());
        $this->assertSame('member:2nd', $iterator->current());
        $this->assertSame(1, $iterator->key());

        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
