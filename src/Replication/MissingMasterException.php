<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Replication;

use Predis\ClientException;

/**
 * Exception class that identifies when master is missing in a replication setup.
 */
class MissingMasterException extends ClientException
{
}
