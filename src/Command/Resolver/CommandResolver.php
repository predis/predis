<?php

namespace Predis\Command\Resolver;

class CommandResolver implements CommandResolverInterface
{
    private const COMMANDS_NAMESPACE = "Predis\Command\Redis";

    /**
     * @var array
     */
    private $modules;

    public function __construct(array $modules)
    {
        $this->modules = $modules;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $commandID): ?string
    {
        if (class_exists($commandClass = self::COMMANDS_NAMESPACE . "\\" . $commandID)) {
            return $commandClass;
        }

        $commandModule = $this->resolveCommandModuleByPrefix($commandID);

        if (null === $commandModule) {
            return null;
        }

        if (class_exists($commandClass = self::COMMANDS_NAMESPACE . "\\" . $commandModule . "\\" . $commandID)) {
            return $commandClass;
        }

        return null;
    }

    private function resolveCommandModuleByPrefix(string $commandID): ?string
    {
        foreach ($this->modules as $module) {
            if (preg_match("/^{$module['commandPrefix']}/", $commandID)) {
                return $module['name'];
            }
        }
        return null;
    }
}
