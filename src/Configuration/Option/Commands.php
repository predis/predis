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

namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Command\FactoryInterface;
use Predis\Command\RawFactory;
use Predis\Command\RedisFactory;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;

/**
 * Configures a connection factory to be used by the client.
 */
class Commands implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $options);
        }

        if ($value instanceof FactoryInterface) {
            return $value;
        } elseif (is_array($value)) {
            return $this->createFactoryByArray($options, $value);
        } elseif (is_string($value)) {
            return $this->createFactoryByString($options, $value);
        } else {
            throw new InvalidArgumentException(sprintf(
                '%s expects a valid command factory',
                static::class
            ));
        }
    }

    /**
     * Creates a new default command factory from a named array.
     *
     * The factory instance is configured according to the supplied named array
     * mapping command IDs (passed as keys) to the FCQN of classes implementing
     * Predis\Command\CommandInterface.
     *
     * @param OptionsInterface $options Client options container
     * @param array            $value   Named array mapping command IDs to classes
     *
     * @return FactoryInterface
     */
    protected function createFactoryByArray(OptionsInterface $options, array $value)
    {
        /**
         * @var FactoryInterface
         */
        $commands = $this->getDefault($options);

        foreach ($value as $commandID => $commandClass) {
            if ($commandClass === null) {
                $commands->undefine($commandID);
            } else {
                $commands->define($commandID, $commandClass);
            }
        }

        return $commands;
    }

    /**
     * Creates a new command factory from a descriptive string.
     *
     * The factory instance is configured according to the supplied descriptive
     * string that identifies specific configurations of schemes and connection
     * classes. Supported configuration values are:
     *
     * - "predis" returns the default command factory used by Predis
     * - "raw" returns a command factory that creates only raw commands
     * - "default" is simply an alias of "predis"
     *
     * @param OptionsInterface $options Client options container
     * @param string           $value   Descriptive string identifying the desired configuration
     *
     * @return FactoryInterface
     */
    protected function createFactoryByString(OptionsInterface $options, string $value)
    {
        switch (strtolower($value)) {
            case 'default':
            case 'predis':
                return $this->getDefault($options);

            case 'raw':
                return $this->createRawFactory($options);

            default:
                throw new InvalidArgumentException(sprintf(
                    '%s does not recognize `%s` as a supported configuration string',
                    static::class,
                    $value
                ));
        }
    }

    /**
     * Creates a new raw command factory instance.
     *
     * @param OptionsInterface $options Client options container
     */
    protected function createRawFactory(OptionsInterface $options): FactoryInterface
    {
        $commands = new RawFactory();

        if (isset($options->prefix)) {
            throw new InvalidArgumentException(sprintf(
                '%s does not support key prefixing', RawFactory::class
            ));
        }

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        $commands = new RedisFactory();

        if (isset($options->prefix)) {
            $commands->setProcessor($options->prefix);
        }

        return $commands;
    }
}
