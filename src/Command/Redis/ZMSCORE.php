<?php
/*
 * This file is part of the Predis package.
 *
 * (c) Vladyslav Vildanov <vladyslav.vildanov@redis.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

declare(strict_types=1);

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/zmscore/
 *
 * @author Vladyslav Vildanov <vladyslav.vildanov@redis.com>
 * @version >= 6.2.0
 */
class ZMSCORE extends RedisCommand
{
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return 'ZMSCORE';
    }
}
