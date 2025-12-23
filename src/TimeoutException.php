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

namespace Predis;

use Predis\Connection\NodeConnectionInterface;
use Throwable;

class TimeoutException extends CommunicationException
{
    public function __construct(NodeConnectionInterface $connection, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($connection, 'Operation has timed out', $code, $previous);
    }
}
