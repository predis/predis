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

namespace Predis\Command\Resolver;

use Predis\ClientConfiguration;

class CommandResolver implements CommandResolverInterface
{
    private const COMMANDS_NAMESPACE = "Predis\Command\Redis";

    /**
     * @var array
     */
    private $modules;

    public function __construct()
    {
        $this->modules = ClientConfiguration::getModules();
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(string $commandID): ?string
    {
        if (class_exists($commandClass = self::COMMANDS_NAMESPACE . '\\' . $commandID)) {
            return $commandClass;
        }

        $commandModule = $this->resolveCommandModuleByPrefix($commandID);

        if (null === $commandModule) {
            return null;
        }

        if (class_exists($commandClass = self::COMMANDS_NAMESPACE . '\\' . $commandModule . '\\' . $commandID)) {
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
