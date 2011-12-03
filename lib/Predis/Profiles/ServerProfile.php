<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Profiles;

use Predis\ClientException;
use Predis\Commands\Processors\ICommandProcessor;
use Predis\Commands\Processors\IProcessingSupport;

/**
 * Base class that implements common functionalities of server profiles.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class ServerProfile implements IServerProfile, IProcessingSupport
{
    private static $profiles;

    private $commands;
    private $processor;

    /**
     *
     */
    public function __construct()
    {
        $this->commands = $this->getSupportedCommands();
    }

    /**
     * Returns a map of all the commands supported by the profile and their
     * actual PHP classes.
     *
     * @return array
     */
    protected abstract function getSupportedCommands();

    /**
     * Returns the default server profile.
     *
     * @return IServerProfile
     */
    public static function getDefault()
    {
        return self::get('default');
    }

    /**
     * Returns the development server profile.
     *
     * @return IServerProfile
     */
    public static function getDevelopment()
    {
        return self::get('dev');
    }

    /**
     * Returns a map of all the server profiles supported by default and their
     * actual PHP classes.
     *
     * @return array
     */
    private static function getDefaultProfiles()
    {
        return array(
            '1.2'     => 'Predis\Profiles\ServerVersion12',
            '2.0'     => 'Predis\Profiles\ServerVersion20',
            '2.2'     => 'Predis\Profiles\ServerVersion22',
            '2.4'     => 'Predis\Profiles\ServerVersion24',
            'default' => 'Predis\Profiles\ServerVersion24',
            'dev'     => 'Predis\Profiles\ServerVersionNext',
        );
    }

    /**
     * Registers a new server profile.
     *
     * @param string $alias Profile version or alias.
     * @param string $profileClass FQN of a class implementing Predis\Profiles\IServerProfile.
     */
    public static function define($alias, $profileClass)
    {
        if (!isset(self::$profiles)) {
            self::$profiles = self::getDefaultProfiles();
        }

        $profileReflection = new \ReflectionClass($profileClass);

        if (!$profileReflection->isSubclassOf('Predis\Profiles\IServerProfile')) {
            throw new \InvalidArgumentException("Cannot register '$profileClass' as it is not a valid profile class");
        }

        self::$profiles[$alias] = $profileClass;
    }

    /**
     * Returns the specified server profile.
     *
     * @param string $version Profile version or alias.
     * @return IServerProfile
     */
    public static function get($version)
    {
        if (!isset(self::$profiles)) {
            self::$profiles = self::getDefaultProfiles();
        }
        if (!isset(self::$profiles[$version])) {
            throw new ClientException("Unknown server profile: $version");
        }

        $profile = self::$profiles[$version];

        return new $profile();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCommands(Array $commands)
    {
        foreach ($commands as $command) {
            if ($this->supportsCommand($command) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCommand($command)
    {
        return isset($this->commands[strtolower($command)]);
    }

    /**
     * Returns the FQN of the class that represent the specified command ID
     * registered in the current server profile.
     *
     * @param string $command Command ID.
     * @return string
     */
    public function getCommandClass($command)
    {
        if (isset($this->commands[$command = strtolower($command)])) {
            return $this->commands[$command];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($method, $arguments = array())
    {
        $method = strtolower($method);
        if (!isset($this->commands[$method])) {
            throw new ClientException("'$method' is not a registered Redis command");
        }

        $commandClass = $this->commands[$method];
        $command = new $commandClass();
        $command->setArguments($arguments);

        if (isset($this->processor)) {
            $this->processor->process($command);
        }

        return $command;
    }

    /**
     * Defines new commands in the server profile.
     *
     * @param array $commands Named list of command IDs and their classes.
     */
    public function defineCommands(Array $commands)
    {
        foreach ($commands as $alias => $command) {
            $this->defineCommand($alias, $command);
        }
    }

    /**
     * Defines a new commands in the server profile.
     *
     * @param string $alias Command ID.
     * @param string $command FQN of a class implementing Predis\Commands\ICommand.
     */
    public function defineCommand($alias, $command)
    {
        $commandReflection = new \ReflectionClass($command);
        if (!$commandReflection->isSubclassOf('Predis\Commands\ICommand')) {
            throw new \InvalidArgumentException("Cannot register '$command' as it is not a valid Redis command");
        }
        $this->commands[strtolower($alias)] = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(ICommandProcessor $processor = null)
    {
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Returns the version of server profile as its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getVersion();
    }
}
