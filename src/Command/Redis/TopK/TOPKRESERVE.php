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

namespace Predis\Command\Redis\TopK;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/topk.reserve/
 *
 * Initializes a TopK with specified parameters.
 */
class TOPKRESERVE extends RedisCommand
{
    public function getId()
    {
        return 'TOPK.RESERVE';
    }

    public function setArguments(array $arguments)
    {
        switch (count($arguments)) {
            case 3:
                $arguments[] = 7; // default depth
                $arguments[] = 0.9; // default decay
                break;
            case 4:
                $arguments[] = 0.9; // default decay
                break;
            default:
                parent::setArguments($arguments);

                return;
        }

        parent::setArguments($arguments);
    }
}
