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

use Predis\Distribution\HashGeneratorInterface;

/**
 * Defines an abstraction representing a Redis command.
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface CommandInterface
{
    /**
     * Gets the ID of a Redis command.
     *
     * @return string
     */
    public function getId();

    /**
     * Returns an hash of the command using the provided algorithm against the
     * key (used to calculate the distribution of keys with client-side sharding).
     *
     * @param HashGeneratorInterface $hasher Distribution algorithm.
     * @return int
     */
    public function getHash(HashGeneratorInterface $hasher);

    /**
     * Sets the arguments of the command.
     *
     * @param array $arguments List of arguments.
     */
    public function setArguments(Array $arguments);

    /**
     * Gets the arguments of the command.
     *
     * @return array
     */
    public function getArguments();

    /**
     * Parses a reply buffer and returns a PHP object.
     *
     * @param string $data Binary string containing the whole reply.
     * @return mixed
     */
    public function parseResponse($data);
}
