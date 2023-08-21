<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use Predis\Command\Traits\Contract\ClusterableContract;

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
 */
final class RawCommand implements CommandInterface, Clusterable
{
    use ClusterableContract;

    private $commandID;
    private $arguments;
    private $keys;

    /**
     * @param string     $commandID Command ID
     * @param array      $arguments Command arguments
     * @param array|null $keys      Keys arguments from given arguments set
     */
    public function __construct($commandID, array $arguments = [], array $keys = null)
    {
        $this->commandID = strtoupper($commandID);
        $this->setArguments($arguments);
        $this->keys = $keys;
    }

    /**
     * Creates a new raw command using a variadic method.
     *
     * @param string $commandID Redis command ID
     * @param string ...$args   Arguments list for the command
     *
     * @return CommandInterface
     */
    public static function create($commandID, ...$args)
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
    public function parseResponse($data)
    {
        return $data;
    }

    public function getKeys(): ?array
    {
        return $this->keys;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResp3Response($data)
    {
        return $data;
    }
}
