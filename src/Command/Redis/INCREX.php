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

    public const EXPIRE_EX = 'EX';
    public const EXPIRE_PX = 'PX';
    public const EXPIRE_EXAT = 'EXAT';
    public const EXPIRE_PXAT = 'PXAT';
    public const EXPIRE_PERSIST = 'PERSIST';

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
     * Arguments: [key, value, ?lbound, ?ubound, ?saturate, ?expireType, ?expireValue, ?enx]
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

        if (!empty($arguments[4])) {
            $processed[] = 'SATURATE';
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
     * {@inheritdoc}
     *
     * Normalizes the response to native numeric types so RESP2 and RESP3 are
     * consistent: RESP2 returns BYFLOAT results as bulk strings while RESP3
     * returns native doubles. After parsing, callers always see int|float.
     */
    public function parseResponse($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        return array_map(static function ($v) {
            if (!is_string($v) || !is_numeric($v)) {
                return $v;
            }

            return strpbrk($v, '.eE') !== false ? (float) $v : (int) $v;
        }, $data);
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
