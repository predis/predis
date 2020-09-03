<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Processor;

use PredisTestCase;
use Predis\Command\CommandInterface;

/**
 *
 */
class ProcessorChainTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructor(): void
    {
        $chain = new ProcessorChain();

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $chain);
        $this->assertEmpty($chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithProcessorsArray(): void
    {
        $processors = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain($processors);

        $this->assertSame($processors, $chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testCountProcessors(): void
    {
        $processors = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain($processors);

        $this->assertEquals(2, $chain->count());
    }

    /**
     * @group disconnected
     */
    public function testAddProcessors(): void
    {
        /** @var ProcessorInterface[] */
        $processors = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain();
        $chain->add($processors[0]);
        $chain->add($processors[1]);

        $this->assertSame($processors, $chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testAddMoreProcessors(): void
    {
        /** @var ProcessorInterface */
        $processors1 = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        /** @var ProcessorInterface */
        $processors2 = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain($processors1);
        $chain->add($processors2[0]);
        $chain->add($processors2[1]);

        $this->assertSame(array_merge($processors1, $processors2), $chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testRemoveProcessors(): void
    {
        /** @var ProcessorInterface */
        $processors = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain($processors);

        $chain->remove($processors[0]);
        $this->assertSame(array($processors[1]), $chain->getProcessors());

        $chain->remove($processors[1]);
        $this->assertEmpty($chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testRemoveProcessorNotInChain(): void
    {
        /** @var ProcessorInterface */
        $processor = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();

        $processors = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain($processors);
        $chain->remove($processor);

        $this->assertSame($processors, $chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testRemoveProcessorFromEmptyChain(): void
    {
        /** @var ProcessorInterface */
        $processor = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();

        $chain = new ProcessorChain();
        $this->assertEmpty($chain->getProcessors());

        $chain->remove($processor);
        $this->assertEmpty($chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testOffsetGet(): void
    {
        $processors = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain($processors);

        $this->assertSame($processors[0], $chain[0]);
        $this->assertSame($processors[1], $chain[1]);
    }

    /**
     * @group disconnected
     */
    public function testOffsetIsset(): void
    {
        $processors = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain($processors);

        $this->assertTrue(isset($chain[0]));
        $this->assertTrue(isset($chain[1]));
        $this->assertFalse(isset($chain[2]));
    }

    /**
     * @group disconnected
     */
    public function testOffsetSet(): void
    {
        $processor = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();

        $chain = new ProcessorChain();
        $chain[0] = $processor;

        $this->assertSame($processor, $chain[0]);
    }

    /**
     * @group disconnected
     */
    public function testOffsetSetWithInvalidType(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Processor chain accepts only instances of `Predis\Command\Processor\ProcessorInterface`');

        $chain = new ProcessorChain();
        $chain[0] = new \stdClass();
    }

    /**
     * @group disconnected
     */
    public function testGetIterator(): void
    {
        $processors = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

        $chain = new ProcessorChain($processors);

        $this->assertSame($processors, iterator_to_array($chain->getIterator()));
    }

    /**
     * @group disconnected
     */
    public function testProcessChain(): void
    {
        /** @var CommandInterface */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();

        $processor1 = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();
        $processor1
            ->expects($this->once())
            ->method('process')
            ->with($command);

        $processor2 = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();
        $processor2
            ->expects($this->once())
            ->method('process')
            ->with($command);

        $processors = array($processor1, $processor2);

        $chain = new ProcessorChain($processors);
        $chain->process($command);
    }
}
