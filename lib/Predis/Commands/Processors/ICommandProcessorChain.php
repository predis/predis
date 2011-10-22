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

/**
 * A command processor chain processes a command using multiple chained command
 * processor before it is sent to Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ICommandProcessorChain extends ICommandProcessor, \IteratorAggregate, \Countable
{
    /**
     * Adds a command processor.
     *
     * @param ICommandProcessor $processor A command processor.
     */
    public function add(ICommandProcessor $processor);

    /**
     * Removes a command processor from the chain.
     *
     * @param ICommandProcessor $processor A command processor.
     */
    public function remove(ICommandProcessor $processor);

    /**
     * Returns an ordered list of the command processors in the chain.
     *
     * @return array
     */
    public function getProcessors();
}
