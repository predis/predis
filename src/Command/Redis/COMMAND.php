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

namespace Predis\Command\Redis;

use Predis\Command\Command as BaseCommand;

/**
 * @see http://redis.io/commands/command
 */
class COMMAND extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'COMMAND';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        // Relay (RESP3) uses maps and it might be good
        // to make the return value a breaking change

        return $data;
    }
}
