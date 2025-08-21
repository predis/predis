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

namespace Predis\Transaction\Strategy;

use Predis\Connection\Replication\ReplicationInterface;
use Predis\Transaction\MultiExecState;

class ReplicationConnectionStrategy extends NonClusterConnectionStrategy
{
    /**
     * @var ReplicationInterface
     */
    protected $connection;

    public function __construct(ReplicationInterface $connection, MultiExecState $state)
    {
        parent::__construct($connection, $state);
    }
}
