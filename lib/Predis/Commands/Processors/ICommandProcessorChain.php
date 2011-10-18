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

interface ICommandProcessorChain extends ICommandProcessor, \IteratorAggregate, \Countable
{

    public function add(ICommandProcessor $preprocessor);
    public function remove(ICommandProcessor $preprocessor);
    public function getProcessors();
}
