<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * Base class used to implement an higher level abstraction for commands based
 * on Lua scripting with EVAL and EVALSHA.
 *
 * @see http://redis.io/commands/eval
 */
abstract class ScriptCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EVALSHA';
    }

    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    abstract public function getScript();

    /**
     * Calculates the SHA1 hash of the body of the script.
     *
     * @return string SHA1 hash.
     */
    public function getScriptHash()
    {
        return sha1($this->getScript());
    }

    /**
     * Specifies the number of arguments that should be considered as keys.
     *
     * The default behaviour for the base class is to return 0 to indicate that
     * all the elements of the arguments array should be considered as keys, but
     * subclasses can enforce a static number of keys.
     *
     * @return int
     */
    protected function getKeysCount()
    {
        return 0;
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
    public function setArguments(array $arguments)
    {
        if (($numkeys = $this->getKeysCount()) && $numkeys < 0) {
            $numkeys = count($arguments) + $numkeys;
        }

        $arguments = array_merge([$this->getScriptHash(), (int) $numkeys], $arguments);

        parent::setArguments($arguments);
    }

    /**
     * Returns arguments for EVAL command.
     *
     * @return array
     */
    public function getEvalArguments()
    {
        $arguments = $this->getArguments();
        $arguments[0] = $this->getScript();

        return $arguments;
    }

    /**
     * Returns the equivalent EVAL command as a raw command instance.
     *
     * @return RawCommand
     */
    public function getEvalCommand()
    {
        return new RawCommand('EVAL', $this->getEvalArguments());
    }
}
