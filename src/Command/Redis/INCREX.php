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
 * @see http://redis.io/commands/increx
 */
class INCREX extends RedisCommand
{
    public const BY_INT = 'BYINT';
    public const BY_FLOAT = 'BYFLOAT';

    public const OVERFLOW_FAIL = 'FAIL';
    public const OVERFLOW_SAT = 'SAT';
    public const OVERFLOW_REJECT = 'REJECT';

    public const EXPIRE_EX = 'EX';
    public const EXPIRE_PX = 'PX';
    public const EXPIRE_EXAT = 'EXAT';
    public const EXPIRE_PXAT = 'PXAT';
    public const EXPIRE_PERSIST = 'PERSIST';

    /**
     * @var string[]
     */
    private static $byEnum = [self::BY_INT, self::BY_FLOAT];

    /**
     * @var string[]
     */
    private static $overflowEnum = [
        self::OVERFLOW_FAIL,
        self::OVERFLOW_SAT,
        self::OVERFLOW_REJECT,
    ];

    /**
     * @var string[]
     */
    private static $expireEnum = [
        self::EXPIRE_EX,
        self::EXPIRE_PX,
        self::EXPIRE_EXAT,
        self::EXPIRE_PXAT,
        self::EXPIRE_PERSIST,
    ];

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'INCREX';
    }

    /**
     * {@inheritdoc}
     *
     * Arguments: [key, value, ?lbound, ?ubound, ?overflow, ?expireType, ?expireValue, ?enx]
     *
     * Value is required at the public API layer. The increment type (BYINT or BYFLOAT)
     * is inferred from the runtime type of $value:
     *   - int       → BYINT
     *   - float     → BYFLOAT
     *   - string    → must be numeric. BYFLOAT when it contains a decimal point or
     *                 exponent, BYINT otherwise.
     */
    public function setArguments(array $arguments)
    {
        $processed = [$arguments[0]];

        if (array_key_exists(1, $arguments)) {
            $byType = $this->resolveByType($arguments[1]);
            $processed[] = $byType;
            $processed[] = $arguments[1];
        }

        if (isset($arguments[2]) && $arguments[2] !== null) {
            $processed[] = 'LBOUND';
            $processed[] = $arguments[2];
        }

        if (isset($arguments[3]) && $arguments[3] !== null) {
            $processed[] = 'UBOUND';
            $processed[] = $arguments[3];
        }

        $overflow = $arguments[4] ?? null;
        if ($overflow !== null && $overflow !== '') {
            $overflow = strtoupper($overflow);

            if (!in_array($overflow, self::$overflowEnum, true)) {
                $allowed = implode(', ', self::$overflowEnum);
                throw new UnexpectedValueException("Overflow policy accepts only: {$allowed} values");
            }

            $processed[] = 'OVERFLOW';
            $processed[] = $overflow;
        }

        $expireType = $arguments[5] ?? null;
        if ($expireType !== null && $expireType !== '') {
            $expireType = strtoupper($expireType);

            if (!in_array($expireType, self::$expireEnum, true)) {
                $allowed = implode(', ', self::$expireEnum);
                throw new UnexpectedValueException("Expire modifier accepts only: {$allowed} values");
            }

            if ($expireType === self::EXPIRE_PERSIST) {
                $processed[] = self::EXPIRE_PERSIST;
            } else {
                if (!array_key_exists(6, $arguments) || $arguments[6] === null) {
                    throw new UnexpectedValueException("{$expireType} requires a value");
                }

                $processed[] = $expireType;
                $processed[] = $arguments[6];
            }
        }

        if (!empty($arguments[7])) {
            $processed[] = 'ENX';
        }

        parent::setArguments($processed);
    }

    /**
     * @param  int|float|string $value
     * @return string           Either BYINT or BYFLOAT
     */
    private function resolveByType($value): string
    {
        if (is_int($value)) {
            return self::BY_INT;
        }

        if (is_float($value)) {
            return self::BY_FLOAT;
        }

        if (!is_string($value) || !is_numeric($value)) {
            throw new UnexpectedValueException(
                'Increment value must be an int, float, or numeric string'
            );
        }

        // Numeric string: pick BYFLOAT when it carries a decimal point or
        // exponent, otherwise treat it as an integer.
        if (strpbrk($value, '.eE') !== false) {
            return self::BY_FLOAT;
        }

        return self::BY_INT;
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
