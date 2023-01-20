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

namespace Predis\Configuration;

use Predis\Command\Processor\ProcessorInterface;

/**
 * @property callable                            $aggregate   Custom aggregate connection initializer
 * @property callable                            $cluster     Aggregate connection initializer for clustering
 * @property \Predis\Connection\FactoryInterface $connections Connection factory for creating new connections
 * @property bool                                $exceptions  Toggles exceptions in client for -ERR responses
 * @property ProcessorInterface                  $prefix      Key prefixing strategy using the supplied string as prefix
 * @property \Predis\Command\FactoryInterface    $commands    Command factory for creating Redis commands
 * @property callable                            $replication Aggregate connection initializer for replication
 */
interface OptionsInterface
{
    /**
     * Returns the default value for the given option.
     *
     * @param string $option Name of the option
     *
     * @return mixed|null
     */
    public function getDefault($option);

    /**
     * Checks if the given option has been set by the user upon initialization.
     *
     * @param string $option Name of the option
     *
     * @return bool
     */
    public function defined($option);

    /**
     * Checks if the given option has been set and does not evaluate to NULL.
     *
     * @param string $option Name of the option
     *
     * @return bool
     */
    public function __isset($option);

    /**
     * Returns the value of the given option.
     *
     * @param string $option Name of the option
     *
     * @return mixed|null
     */
    public function __get($option);
}
