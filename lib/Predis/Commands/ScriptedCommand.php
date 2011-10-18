<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

/**
 * Base class used to implement an higher level abstraction for "virtual"
 * commands based on EVAL.
 *
 * @link http://redis.io/commands/eval
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class ScriptedCommand extends ServerEval
{
    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    public abstract function getScript();

    /*
     * Gets the number of arguments that should be considered as keys.
     *
     * @return int
     */
    protected function keysCount()
    {
        // The default behaviour for the base class is to use all the arguments
        // passed to a scripted command to populate the KEYS table in Lua.
        return count($this->getArguments());
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(Array $arguments)
    {
        return array_merge(array($this->getScript(), $this->keysCount()), $arguments);
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeys()
    {
        return array_slice($this->getArguments(), 2, $this->keysCount());
    }
}
