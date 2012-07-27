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
 * Base class for Redis commands.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class AbstractCommand implements CommandInterface
{
    private $hash;
    private $arguments = array();

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
        $this->arguments = $this->filterArguments($arguments);
        unset($this->hash);
    }

    /**
     * Sets the arguments array without filtering.
     *
     * @param array $arguments List of arguments.
     */
    public function setRawArguments(Array $arguments)
    {
        $this->arguments = $arguments;
        unset($this->hash);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Gets the argument from the arguments list at the specified index.
     *
     * @param array $arguments Position of the argument.
     */
    public function getArgument($index = 0)
    {
        if (isset($this->arguments[$index]) === true) {
            return $this->arguments[$index];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function getHash()
    {
        if (isset($this->hash)) {
            return $this->hash;
        }
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
