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

namespace Predis\Command\Redis\Container;

use Predis\ClientConfiguration;
use Predis\ClientInterface;
use UnexpectedValueException;

class ContainerFactory
{
    private const CONTAINER_NAMESPACE = "Predis\Command\Redis\Container";

    /**
     * Mappings for class names that corresponds to PHP reserved words.
     *
     * @var array
     */
    private static $specialMappings = [
        'FUNCTION' => FunctionContainer::class,
    ];

    /**
     * Creates container command.
     *
     * @param  ClientInterface    $client
     * @param  string             $containerCommandID
     * @return ContainerInterface
     */
    public static function create(ClientInterface $client, string $containerCommandID): ContainerInterface
    {
        $containerCommandID = strtoupper($containerCommandID);
        $commandModule = self::resolveCommandModuleByPrefix($containerCommandID);

        if (null !== $commandModule) {
            if (class_exists($containerClass = self::CONTAINER_NAMESPACE . '\\' . $commandModule . '\\' . $containerCommandID)) {
                return new $containerClass($client);
            }

            throw new UnexpectedValueException('Given module container command is not supported.');
        }

        if (class_exists($containerClass = self::CONTAINER_NAMESPACE . '\\' . $containerCommandID)) {
            return new $containerClass($client);
        }

        if (array_key_exists($containerCommandID, self::$specialMappings)) {
            $containerClass = self::$specialMappings[$containerCommandID];

            return new $containerClass($client);
        }

        throw new UnexpectedValueException('Given container command is not supported.');
    }

    /**
     * @param  string      $commandID
     * @return string|null
     */
    private static function resolveCommandModuleByPrefix(string $commandID): ?string
    {
        $modules = ClientConfiguration::getModules();

        foreach ($modules as $module) {
            if (preg_match("/^{$module['commandPrefix']}/", $commandID)) {
                return $module['name'];
            }
        }

        return null;
    }
}
