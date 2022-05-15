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
 * Class representing a generic Redis command.
 *
 * Arguments and responses for these commands are not normalized and they follow
 * what is defined by the Redis documentation.
 *
 * Raw commands can be useful when implementing higher level abstractions on top
 * of Predis\Client or managing internals like Redis Sentinel or Cluster as they
 * are not potentially subject to hijacking from third party libraries when they
 * override command handlers for standard Redis commands.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
final class RawCommand implements CommandInterface
{
    private $slot;
    private $commandID;
    private $arguments;

    /**
     * @param string $commandID Command ID
     * @param array  $arguments Command arguments
     */
    public function __construct($commandID, array $arguments = array())
    {
        $this->commandID = strtoupper($commandID);
        $this->setArguments($arguments);
    }

    /**
     * Creates a new raw command using a variadic method.
     *
     * @param string $commandID Redis command ID
     * @param string ...        Arguments list for the command
     *
     * @return CommandInterface
     */
    public static function create($commandID /* [ $arg, ... */)
    {
        $arguments = func_get_args();

        return new static(array_shift($arguments), $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->commandID;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        unset($this->slot);
    }

    /**
     * {@inheritdoc}
     */
    public function setRawArguments(array $arguments)
    {
        $this->setArguments($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument($index)
    {
        if (isset($this->arguments[$index])) {
            return $this->arguments[$index];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setSlot($slot)
    {
        $this->slot = $slot;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlot()
    {
        if (isset($this->slot)) {
            return $this->slot;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data;
    }
}
