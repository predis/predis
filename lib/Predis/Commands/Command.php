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

use Predis\Helpers;
use Predis\Distribution\INodeKeyGenerator;

/**
 * Base class for Redis commands.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class Command implements ICommand
{
    private $_hash;
    private $_arguments = array();

    /**
     * Returns a filtered array of the arguments.
     *
     * @param array $arguments List of arguments.
     * @return array
     */
    protected function filterArguments(Array $arguments)
    {
        return $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(Array $arguments)
    {
        $this->_arguments = $this->filterArguments($arguments);
        unset($this->_hash);
    }

    /**
     * Sets the arguments array without filtering.
     *
     * @param array $arguments List of arguments.
     */
    public function setRawArguments(Array $arguments)
    {
        $this->_arguments = $arguments;
        unset($this->_hash);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * Gets the argument from the arguments list at the specified index.
     *
     * @param array $arguments Position of the argument.
     */
    public function getArgument($index = 0)
    {
        if (isset($this->_arguments[$index]) === true) {
            return $this->_arguments[$index];
        }
    }

    /**
     * Implements the rule that is used to prefix the keys and returns a new
     * array of arguments with the modified keys.
     *
     * @param array $arguments Arguments of the command.
     * @param string $prefix Prefix appended to each key in the arguments.
     * @return array
     */
    protected function onPrefixKeys(Array $arguments, $prefix)
    {
        $arguments[0] = "$prefix{$arguments[0]}";
        return $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function prefixKeys($prefix)
    {
        $arguments = $this->onPrefixKeys($this->_arguments, $prefix);
        if (isset($arguments)) {
            $this->_arguments = $arguments;
            unset($this->_hash);
        }
    }

    /**
     * Checks if the command can return an hash for client-side sharding.
     *
     * @return Boolean
     */
    protected function canBeHashed()
    {
        return isset($this->_arguments[0]);
    }

    /**
     * Checks if the specified array of keys will generate the same hash.
     *
     * @param array $keys Array of keys.
     * @return Boolean
     */
    protected function checkSameHashForKeys(Array $keys)
    {
        if (($count = count($keys)) === 0) {
            return false;
        }

        $currentKey = Helpers::getKeyHashablePart($keys[0]);

        for ($i = 1; $i < $count; $i++) {
            $nextKey = Helpers::getKeyHashablePart($keys[$i]);
            if ($currentKey !== $nextKey) {
                return false;
            }
            $currentKey = $nextKey;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getHash(INodeKeyGenerator $distributor)
    {
        if (isset($this->_hash)) {
            return $this->_hash;
        }

        if ($this->canBeHashed()) {
            $key = Helpers::getKeyHashablePart($this->_arguments[0]);
            $this->_hash = $distributor->generateKey($key);

            return $this->_hash;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data;
    }

    /**
     * Helper function used to reduce a list of arguments to a string.
     *
     * @param string $accumulator Temporary string.
     * @param string $argument Current argument.
     * @return string
     */
    protected function toStringArgumentReducer($accumulator, $argument)
    {
        if (strlen($argument) > 32) {
            $argument = substr($argument, 0, 32) . '[...]';
        }
        $accumulator .= " $argument";

        return $accumulator;
    }

    /**
     * Returns a partial string representation of the command with its arguments.
     *
     * @return string
     */
    public function __toString()
    {
        return array_reduce(
            $this->getArguments(),
            array($this, 'toStringArgumentReducer'),
            $this->getId()
        );
    }
}
