<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands\Processors;

use Predis\Commands\ICommand;

/**
 * Default implementation of a command processors chain.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ProcessorChain implements ICommandProcessorChain, \ArrayAccess
{
    private $_processors;

    /**
     * @param array $processors List of instances of ICommandProcessor.
     */
    public function __construct($processors = array())
    {
        foreach ($processors as $processor) {
            $this->add($processor);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(ICommandProcessor $processor)
    {
        $this->_processors[] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ICommandProcessor $processor)
    {
        $index = array_search($processor, $this->_processors, true);
        if ($index !== false) {
            unset($this[$index]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ICommand $command)
    {
        $count = count($this->_processors);
        for ($i = 0; $i < $count; $i++) {
            $this->_processors[$i]->process($command);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors()
    {
        return $this->_processors;
    }

    /**
     * Returns an iterator over the list of command processor in the chain.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_processors);
    }

    /**
     * Returns the number of command processors in the chain.
     *
     * @return int
     */
    public function count()
    {
        return count($this->_processors);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($index)
    {
        return isset($this->_processors[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($index)
    {
        return $this->_processors[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($index, $processor)
    {
        if (!$processor instanceof ICommandProcessor) {
            throw new \InvalidArgumentException(
                'A processor chain can hold only instances of classes implementing '.
                'the Predis\Commands\Preprocessors\ICommandProcessor interface'
            );
        }

        $this->_processors[$index] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($index)
    {
        unset($this->_processors[$index]);
    }
}
