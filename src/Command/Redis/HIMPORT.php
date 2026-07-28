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

/**
 * @see https://redis.io/commands/?name=himport
 *
 * Container command corresponds to any HIMPORT *.
 * Represents any HIMPORT command with subcommand as first argument.
 *
 * @experimental This command is experimental and its API may change in a future release.
 */
class HIMPORT extends RedisCommand
{
    public function getId()
    {
        return 'HIMPORT';
    }

    public function setArguments(array $arguments)
    {
        // Fields (PREPARE) and values (SET) may be provided as a nested array.
        // Flatten them while preserving the caller-provided order verbatim: the
        // server canonicalizes fields internally and is authoritative for value
        // count, so the client must never sort, deduplicate or reorder them.
        $flattened = [];

        foreach ($arguments as $argument) {
            if (is_array($argument)) {
                foreach ($argument as $value) {
                    $flattened[] = $value;
                }
            } else {
                $flattened[] = $argument;
            }
        }

        parent::setArguments($flattened);
    }

    public function prefixKeys($prefix)
    {
        $arguments = $this->getArguments();

        // Only HIMPORT SET carries a key (at index 1, after the subcommand).
        // PREPARE/DISCARD/DISCARDALL operate on fieldset names, never keys.
        if (isset($arguments[0], $arguments[1]) && strtoupper($arguments[0]) === 'SET') {
            $arguments[1] = $prefix . $arguments[1];
            $this->setRawArguments($arguments);
        }
    }
}
