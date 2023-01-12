<?php

namespace Predis\Command\Redis\Container;

use InvalidArgumentException;
use Predis\ClientConfiguration;
use Predis\ClientInterface;
use UnexpectedValueException;

class ContainerFactory
{
    private const CONTAINER_NAMESPACE = "Predis\Command\Redis\Container";

    /**
     * Creates container command
     *
     * @param ClientInterface $client
     * @param string $containerCommandID
     * @return ContainerInterface
     */
    public static function create(ClientInterface $client, string $containerCommandID): ContainerInterface
    {
        $containerCommandID = strtoupper($containerCommandID);

        if (class_exists($containerClass = self::CONTAINER_NAMESPACE . "\\" . $containerCommandID)) {
            return new $containerClass($client);
        }

        $containerModule = self::resolveContainerCommandModuleByPrefix($containerCommandID);

        if (null === $containerModule) {
            throw new InvalidArgumentException('Given Redis module is not supported.');
        }

        if (class_exists(
            $containerClass = self::CONTAINER_NAMESPACE . "\\" . $containerModule . "\\" . $containerCommandID)
        ) {
            return new $containerClass($client);
        }

        throw new UnexpectedValueException('Given command is not supported.');
    }

    private static function resolveContainerCommandModuleByPrefix(string $containerCommandID): ?string
    {
        foreach (ClientConfiguration::getModules() as $module) {
            if (preg_match("/^{$module['commandPrefix']}/", $containerCommandID)) {
                return $module['name'];
            }
        }

        return null;
    }
}
