<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration;

use Predis\Command\Processor\ProcessorInterface;
use Predis\Connection\Aggregate\ClusterInterface;
use Predis\Connection\Aggregate\ReplicationInterface;
use Predis\Connection\FactoryInterface;
use Predis\Profile\ProfileInterface;

/**
 * Interface defining a container for client options.
 *
 * @property-read callable             $aggregate   Custom connection aggregator.
 * @property-read ClusterInterface     $cluster     Aggregate connection for clustering.
 * @property-read FactoryInterface     $connections Connection factory.
 * @property-read bool                 $exceptions  Toggles exceptions in client for -ERR responses.
 * @property-read ProcessorInterface   $prefix      Key prefixing strategy using the given prefix.
 * @property-read ProfileInterface     $profile     Server profile.
 * @property-read ReplicationInterface $replication Aggregate connection for replication.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface OptionsInterface
{
    /**
     * Returns the default value for the given option.
     *
     * @param string $option Name of the option.
     *
     * @return mixed|null
     */
    public function getDefault($option);

    /**
     * Checks if the given option has been set by the user upon initialization.
     *
     * @param string $option Name of the option.
     *
     * @return bool
     */
    public function defined($option);

    /**
     * Checks if the given option has been set and does not evaluate to NULL.
     *
     * @param string $option Name of the option.
     *
     * @return bool
     */
    public function __isset($option);

    /**
     * Returns the value of the given option.
     *
     * @param string $option Name of the option.
     *
     * @return mixed|null
     */
    public function __get($option);
}
