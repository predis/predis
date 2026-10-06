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
 * @see https://redis.io/commands/?name=bless
 *
 * Container command corresponds to any BLESS *.
 * Represents any BLESS command with subcommand as first argument.
 */
class BLESS extends RedisCommand
{
    private const KEYED_SUBCOMMANDS = ['GET', 'SET', 'CLEAR'];

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BLESS';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (isset($arguments[0]) && strtoupper($arguments[0]) === 'SCAN') {
            $this->setScanArguments($arguments);
        } else {
            parent::setArguments($arguments);
        }
    }

    /**
     * BLESS SCAN cursor flag [COUNT count].
     *
     * Mirrors the plain SCAN command: options are passed as an array
     * (e.g. ['COUNT' => 10]) and expanded into Redis modifiers.
     *
     * @param  array $arguments
     * @return void
     */
    private function setScanArguments(array $arguments): void
    {
        if (count($arguments) === 4) {
            $options = array_pop($arguments);
            if (is_array($options)) {
                $options = $this->prepareScanOptions($options);
                $arguments = array_merge($arguments, $options);
            }
        }

        parent::setArguments($arguments);
    }

    /**
     * Returns a list of SCAN options and modifiers compatible with Redis.
     *
     * @param  array $options List of options.
     * @return array
     */
    private function prepareScanOptions(array $options): array
    {
        $options = array_change_key_case($options, CASE_UPPER);
        $normalized = [];

        if (!empty($options['COUNT'])) {
            $normalized[] = 'COUNT';
            $normalized[] = $options['COUNT'];
        }

        return $normalized;
    }

    public function prefixKeys($prefix)
    {
        $arguments = $this->getArguments();

        // BLESS GET/SET/CLEAR operate on a key at index 1, right after the subcommand.
        // BLESS SCAN takes a cursor and a flag, never a key.
        if (isset($arguments[0], $arguments[1]) && in_array(strtoupper($arguments[0]), self::KEYED_SUBCOMMANDS, true)) {
            $arguments[1] = $prefix . $arguments[1];
            $this->setRawArguments($arguments);
        }
    }
}
