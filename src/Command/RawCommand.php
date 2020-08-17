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
 * Class for generic "anonymous" Redis commands.
 *
 * This command class does not filter input arguments or parse responses, but
 * can be used to leverage the standard Predis API to execute any command simply
 * by providing the needed arguments following the command signature as defined
 * by Redis in its documentation.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RawCommand implements CommandInterface
{
    private $slot;
    private $commandID;
    private $arguments;

    /**
     * @param string $commandID Command ID.
     * @param array  $arguments Command arguments.
     */
    public function __construct($commandID, array $arguments = array())
    {
        $this->commandID = strtoupper($commandID);
        $this->setArguments($arguments);
    }

    /**
     * Creates a new raw command using a variadic method.
     *
     * @param string $commandID Redis command ID.
     * @param string ...        Arguments list for the command.
     *
     * @return CommandInterface
     */
    public static function create($commandID /* [ $arg, ... */)
    {
        $arguments = func_get_args();
        $command = new static(array_shift($arguments), $arguments);

        return $command;
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
