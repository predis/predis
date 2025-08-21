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

    public static function deserializeCommand(string $serializedCommand): CommandInterface
    {
        if ($serializedCommand[0] !== '*') {
            throw new UnexpectedValueException('Invalid serializing format');
        }

        $commandArray = explode("\r\n", $serializedCommand);
        $commandId = $commandArray[2];
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
        $arguments = [];

        for ($i = 4, $iMax = count($commandArray); $i < $iMax; $i++) {
            $arguments[] = $commandArray[$i];
            ++$i;
        }

        $command->setArguments($arguments);

        return $command;
    }
}
