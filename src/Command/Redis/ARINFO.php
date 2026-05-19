<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;
use Predis\Command\Redis\Utils\CommandUtility;

/**
 * @see http://redis.io/commands/arinfo
 */
class ARINFO extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ARINFO';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $processed = [$arguments[0]];

        if (!empty($arguments[1])) {
            $processed[] = 'FULL';
        }

        parent::setArguments($processed);
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_array($data) && $data === array_values($data)) {
            return CommandUtility::arrayToDictionary($data);
        }

        return $data;
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
