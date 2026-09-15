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
 * Base class for Redis commands.
 */
abstract class Command implements CommandInterface
{
    private $slot;
    private $arguments = [];

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
        $this->arguments = $arguments;
        unset($this->slot);
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
     * Normalizes the arguments array passed to a Redis command.
     *
     * @param array $arguments Arguments for a command.
     *
     * @return array
     */
    public static function normalizeArguments(array $arguments)
    {
        if (count($arguments) === 1 && isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }

        return $arguments;
    }

    /**
     * Normalizes the arguments array passed to a variadic Redis command.
     *
     * @param array $arguments Arguments for a command.
     *
     * @return array
     */
    public static function normalizeVariadic(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge([$arguments[0]], $arguments[1]);
        }

        return $arguments;
    }

    /**
     * Remove all false values from arguments.
     *
     * @return void
     */
    public function filterArguments(): void
    {
        $this->arguments = array_filter($this->arguments, static function ($argument) {
            return $argument !== false && $argument !== null;
        });
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
