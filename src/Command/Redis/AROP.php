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
use UnexpectedValueException;

/**
 * @see http://redis.io/commands/arop
 */
class AROP extends RedisCommand
{
    public const SUM = 'SUM';
    public const MIN = 'MIN';
    public const MAX = 'MAX';
    public const AND_OP = 'AND';
    public const OR_OP = 'OR';
    public const XOR_OP = 'XOR';
    public const MATCH_OP = 'MATCH';
    public const USED = 'USED';

    /**
     * @var string[]
     */
    private static $operations = [
        self::SUM, self::MIN, self::MAX,
        self::AND_OP, self::OR_OP, self::XOR_OP,
        self::MATCH_OP, self::USED,
    ];

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'AROP';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        // [key, start, end, operation, ?matchValue]
        if (count($arguments) < 4) {
            parent::setArguments($arguments);

            return;
        }

        $operation = strtoupper($arguments[3]);

        if (!in_array($operation, self::$operations, true)) {
            $allowed = implode(', ', self::$operations);
            throw new UnexpectedValueException(
                "Operation argument accepts only: {$allowed} values"
            );
        }

        $processed = [$arguments[0], $arguments[1], $arguments[2], $operation];

        if ($operation === self::MATCH_OP) {
            if (!array_key_exists(4, $arguments)) {
                throw new UnexpectedValueException('MATCH operation requires a value argument');
            }
            $processed[] = $arguments[4];
        }

        parent::setArguments($processed);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
