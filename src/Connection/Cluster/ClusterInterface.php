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

namespace Predis\Connection\Cluster;

use Predis\Connection\AggregateConnectionInterface;

/**
 * Defines a cluster of Redis servers formed by aggregating multiple connection
 * instances to single Redis nodes.
 */
interface ClusterInterface extends AggregateConnectionInterface
{
}
