<?php

namespace Predis\Profiles;

use Predis\ClientException;

abstract class ServerProfile implements IServerProfile {
    private static $_profiles;
    private $_registeredCommands;

    public function __construct() {
        $this->_registeredCommands = $this->getSupportedCommands();
    }

    protected abstract function getSupportedCommands();

    public static function getDefault() {
        return self::get('default');
    }

    public static function getDevelopment() {
        return self::get('dev');
    }

    private static function getDefaultProfiles() {
        return array(
            '1.2'     => '\Predis\Profiles\Server_v1_2',
            '2.0'     => '\Predis\Profiles\Server_v2_0',
            '2.2'     => '\Predis\Profiles\Server_v2_2',
            'default' => '\Predis\Profiles\Server_v2_2',
            'dev'     => '\Predis\Profiles\Server_vNext',
        );
    }

    public static function registerProfile($profileClass, $aliases) {
        if (!isset(self::$_profiles)) {
            self::$_profiles = self::getDefaultProfiles();
        }

        $profileReflection = new \ReflectionClass($profileClass);
        if (!$profileReflection->isSubclassOf('\Predis\Profiles\IServerProfile')) {
            throw new ClientException(
                "Cannot register '$profileClass' as it is not a valid profile class"
            );
        }

        if (is_array($aliases)) {
            foreach ($aliases as $alias) {
                self::$_profiles[$alias] = $profileClass;
            }
        }
        else {
            self::$_profiles[$aliases] = $profileClass;
        }
    }

    public static function get($version) {
        if (!isset(self::$_profiles)) {
            self::$_profiles = self::getDefaultProfiles();
        }
        if (!isset(self::$_profiles[$version])) {
            throw new ClientException("Unknown server profile: $version");
        }
        $profile = self::$_profiles[$version];
        return new $profile();
    }

    public function supportsCommands(Array $commands) {
        foreach ($commands as $command) {
            if ($this->supportsCommand($command) === false) {
                return false;
            }
        }
        return true;
    }

    public function supportsCommand($command) {
        return isset($this->_registeredCommands[$command]);
    }

    public function createCommand($method, $arguments = array()) {
        if (!isset($this->_registeredCommands[$method])) {
            throw new ClientException("'$method' is not a registered Redis command");
        }
        $commandClass = $this->_registeredCommands[$method];
        $command = new $commandClass();
        $command->setArgumentsArray($arguments);
        return $command;
    }

    public function registerCommands(Array $commands) {
        foreach ($commands as $command => $aliases) {
            $this->registerCommand($command, $aliases);
        }
    }

    public function registerCommand($command, $aliases) {
        $commandReflection = new \ReflectionClass($command);

        if (!$commandReflection->isSubclassOf('\Predis\ICommand')) {
            throw new ClientException("Cannot register '$command' as it is not a valid Redis command");
        }

        if (is_array($aliases)) {
            foreach ($aliases as $alias) {
                $this->_registeredCommands[$alias] = $command;
            }
        }
        else {
            $this->_registeredCommands[$aliases] = $command;
        }
    }

    public function __toString() {
        return $this->getVersion();
    }
}
