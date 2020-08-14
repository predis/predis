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

/**
 *
 */
class ProcessorChainTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructor()
    {
        $chain = new ProcessorChain();

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $chain);
        $this->assertEmpty($chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithProcessorsArray()
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
    public function testCountProcessors()
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
    public function testAddProcessors()
    {
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
    public function testAddMoreProcessors()
    {
        $processors1 = array(
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
            $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock(),
        );

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
    public function testRemoveProcessors()
    {
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
    public function testRemoveProcessorNotInChain()
    {
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
    public function testRemoveProcessorFromEmptyChain()
    {
        $processor = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();

        $chain = new ProcessorChain();
        $this->assertEmpty($chain->getProcessors());

        $chain->remove($processor);
        $this->assertEmpty($chain->getProcessors());
    }

    /**
     * @group disconnected
     */
    public function testProcessChain()
    {
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();

        $processor1 = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();
        $processor1->expects($this->once())->method('process')->with($command);

        $processor2 = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();
        $processor2->expects($this->once())->method('process')->with($command);

        $processors = array($processor1, $processor2);

        $chain = new ProcessorChain($processors);
        $chain->process($command);
    }
}
