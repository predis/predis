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

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see http://redis.io/commands/xcfgset
 *
 * XCFGSET key [IDMP-DURATION duration] [IDMP-MAXSIZE maxsize]
 *
 * Configures the idempotency parameters for a stream's IDMP map.
 */
class XCFGSET extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XCFGSET';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        // IDMP-DURATION option
        if (isset($arguments[1]) && $arguments[1] !== null) {
            array_push($processedArguments, 'IDMP-DURATION', $arguments[1]);
        }

        // IDMP-MAXSIZE option
        if (isset($arguments[2]) && $arguments[2] !== null) {
            array_push($processedArguments, 'IDMP-MAXSIZE', $arguments[2]);
        }

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
