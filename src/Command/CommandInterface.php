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
 * Defines an abstraction representing a Redis command.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface CommandInterface
{
    /**
     * Returns the ID of the Redis command. By convention, command identifiers
     * must always be uppercase.
     *
     * @return string
     */
    public function getId();

    /**
     * Set the hash for the command.
     *
     * @param int $hash Calculated hash.
     */
    public function setHash($hash);

    /**
     * Returns the hash of the command.
     *
     * @return int
     */
    public function getHash();

    /**
     * Sets the arguments for the command.
     *
     * @param array $arguments List of arguments.
     */
    public function setArguments(array $arguments);

    /**
     * Sets the raw arguments for the command without processing them.
     *
     * @param array $arguments List of arguments.
     */
    public function setRawArguments(array $arguments);

    /**
     * Gets the arguments of the command.
     *
     * @return array
     */
    public function getArguments();

    /**
     * Gets the argument of the command at the specified index.
     *
     * @param  int   $index Index of the desired argument.
     * @return mixed
     */
    public function getArgument($index);

    /**
     * Parses a raw response and returns a PHP object.
     *
     * @param  string $data Binary string containing the whole response.
     * @return mixed
     */
    public function parseResponse($data);
}
