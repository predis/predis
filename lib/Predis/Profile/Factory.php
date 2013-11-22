<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Profile;

use InvalidArgumentException;
use ReflectionClass;
use Predis\ClientException;

/**
 * Base class that implements common functionalities of server profiles.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
final class Factory
{
    private static $profiles = array(
        '1.2'     => 'Predis\Profile\RedisVersion120',
        '2.0'     => 'Predis\Profile\RedisVersion200',
        '2.2'     => 'Predis\Profile\RedisVersion220',
        '2.4'     => 'Predis\Profile\RedisVersion240',
        '2.6'     => 'Predis\Profile\RedisVersion260',
        '2.8'     => 'Predis\Profile\RedisVersion280',
        'default' => 'Predis\Profile\RedisVersion280',
        'dev'     => 'Predis\Profile\RedisUnstable',
    );

    /**
     *
     */
    private function __construct()
    {
        // NOOP
    }

    /**
     * Returns the default server profile.
     *
     * @return ProfileInterface
     */
    public static function getDefault()
    {
        return self::get('default');
    }

    /**
     * Returns the development server profile.
     *
     * @return ProfileInterface
     */
    public static function getDevelopment()
    {
        return self::get('dev');
    }

    /**
     * Registers a new server profile.
     *
     * @param string $alias Profile version or alias.
     * @param string $profileClass FQN of a class implementing Predis\Profile\ProfileInterface.
     */
    public static function define($alias, $profileClass)
    {
        $reflection = new ReflectionClass($profileClass);

        if (!$reflection->isSubclassOf('Predis\Profile\ProfileInterface')) {
            throw new InvalidArgumentException("Cannot register '$profileClass' as it is not a valid profile class");
        }

        self::$profiles[$alias] = $profileClass;
    }

    /**
     * Returns the specified server profile.
     *
     * @param string $version Profile version or alias.
     * @return ProfileInterface
     */
    public static function get($version)
    {
        if (!isset(self::$profiles[$version])) {
            throw new ClientException("Unknown server profile: $version");
        }

        $profile = self::$profiles[$version];

        return new $profile();
    }
}
