<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * Base class used to implement an higher level abstraction for "virtual"
 * commands based on EVAL.
 *
 * @link http://redis.io/commands/eval
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class ScriptedCommand extends ServerEvalSHA
{
    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    public abstract function getScript();

    /**
     * Specifies the number of arguments that should be considered as keys.
     *
     * The default behaviour for the base class is to return FALSE to indicate that
     * all the elements of the arguments array should be considered as keys, but
     * subclasses can enforce a static number of keys.
     *
     * @todo How about returning 1 by default to make scripted commands act like
     *       variadic ones where the first argument is the key (KEYS[1]) and the
     *       rest are values (ARGV)?
     *
     * @return int|Boolean
     */
    protected function getKeysCount()
    {
        return false;
    }

    /**
     * Returns the elements from the arguments that are identified as keys.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_slice($this->getArguments(), 2, $this->getKeysCount());
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(Array $arguments)
    {
        if (false !== $numkeys = $this->getKeysCount()) {
            $numkeys = $numkeys >= 0 ? $numkeys : count($arguments) + $numkeys;
        } else {
            $numkeys = count($arguments);
        }

        return array_merge(array(sha1($this->getScript()), $numkeys), $arguments);
    }

    /**
     * @return array
     */
    public function getEvalArguments()
    {
        $arguments = $this->getArguments();
        $arguments[0] = $this->getScript();

        return $arguments;
    }
}
