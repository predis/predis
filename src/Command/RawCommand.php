<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use Predis\ClientConfiguration;
use UnexpectedValueException;

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
final class RawCommand implements CommandInterface
{
    private $slot;
    private $commandID;
    private $arguments;

    /**
     * @param string $commandID Command ID
     * @param array  $arguments Command arguments
     */
    public function __construct($commandID, array $arguments = [])
    {
        $this->commandID = strtoupper($commandID);
        $this->setArguments($arguments);
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
    public function setSlot($slot)
    {
        $this->slot = $slot;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlot()
    {
        return $this->slot ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResp3Response($data)
    {
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeCommand(): string
    {
        $commandID = $this->getId();
        $arguments = $this->getArguments();

        $cmdlen = strlen($commandID);
        $reqlen = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandID}\r\n";

        foreach ($arguments as $argument) {
            $arglen = strlen(strval($argument));
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        return $buffer;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Not binary-safe; see CommandInterface::deserializeCommand().
     *             Scheduled for removal in the next major.
     */
    public static function deserializeCommand(string $serializedCommand): CommandInterface
    {
        $items = self::parseMultibulk($serializedCommand);
        $commandId = $items[0];
        $classPath = __NAMESPACE__ . '\Redis\\';

        // Check if given command is a module command.
        if (count($commandIdArray = explode('.', $commandId)) > 1) {
            // Fetch module configuration to resolve namespace.
            $moduleConfiguration = array_filter(
                ClientConfiguration::getModules(),
                static function ($module) use ($commandIdArray) {
                    return $module['commandPrefix'] === $commandIdArray[0];
                }
            );

            $commandClass = strtoupper($commandIdArray[0] . $commandIdArray[1]);
            $classPath .= array_shift($moduleConfiguration)['name'] . '\\' . $commandClass;
        } else {
            $classPath .= $commandIdArray[0];
        }

        $command = new $classPath();
        $command->setArguments(array_slice($items, 1));

        return $command;
    }

    /**
     * Parses a RESP multibulk buffer into its individual bulk-string values
     * (command ID followed by its arguments), walking each string by its own
     * declared byte length instead of splitting the buffer on "\r\n" -- which
     * a bulk string's payload may legitimately contain (see GHSA-w6f5-v2h6-g786).
     *
     * @param  string   $buffer
     * @return string[]
     */
    private static function parseMultibulk(string $buffer): array
    {
        if ($buffer[0] !== '*') {
            throw new UnexpectedValueException('Invalid serializing format');
        }

        $lineEnd = strpos($buffer, "\r\n");

        if ($lineEnd === false) {
            throw new UnexpectedValueException('Invalid serializing format');
        }

        $count = (int) substr($buffer, 1, $lineEnd - 1);
        $offset = $lineEnd + 2;
        $items = [];

        for ($i = 0; $i < $count; ++$i) {
            if (($buffer[$offset] ?? '') !== '$') {
                throw new UnexpectedValueException('Invalid serializing format');
            }

            $lineEnd = strpos($buffer, "\r\n", $offset);

            if ($lineEnd === false) {
                throw new UnexpectedValueException('Invalid serializing format');
            }

            $bulkLen = (int) substr($buffer, $offset + 1, $lineEnd - $offset - 1);
            $dataStart = $lineEnd + 2;
            $items[] = substr($buffer, $dataStart, $bulkLen);
            $offset = $dataStart + $bulkLen + 2;
        }

        return $items;
    }
}
