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
 * @see https://redis.io/commands/saddex/
 */
class SADDEX extends RedisCommand
{
    public const COND_NULL = '';
    public const COND_MNX = 'mnx';
    public const COND_MXX = 'mxx';

    public const TTL_NULL = '';
    public const TTL_EX = 'ex';
    public const TTL_PX = 'px';
    public const TTL_EXAT = 'exat';
    public const TTL_PXAT = 'pxat';
    public const TTL_KEEP_TTL = 'keepttl';

    /**
     * @var string[]
     */
    private static $conditionEnum = [
        self::COND_MNX => 'MNX',
        self::COND_MXX => 'MXX',
    ];

    /**
     * @var string[]
     */
    private static $ttlModifierEnum = [
        self::TTL_EX => 'EX',
        self::TTL_PX => 'PX',
        self::TTL_EXAT => 'EXAT',
        self::TTL_PXAT => 'PXAT',
        self::TTL_KEEP_TTL => 'KEEPTTL',
    ];

    public function getId()
    {
        return 'SADDEX';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];
        $members = $arguments[1] ?? [];
        $condition = strtolower($arguments[2] ?? self::COND_NULL);
        $ttlModifier = strtolower($arguments[3] ?? self::TTL_NULL);

        if ($condition !== self::COND_NULL) {
            if (!array_key_exists($condition, self::$conditionEnum)) {
                $enumValues = implode(', ', array_keys(self::$conditionEnum));
                throw new UnexpectedValueException("Condition argument accepts only: {$enumValues} values");
            }

            $processedArguments[] = self::$conditionEnum[$condition];
        }

        if ($ttlModifier !== self::TTL_NULL) {
            if (!array_key_exists($ttlModifier, self::$ttlModifierEnum)) {
                $enumValues = implode(', ', array_keys(self::$ttlModifierEnum));
                throw new UnexpectedValueException("Modifier argument accepts only: {$enumValues} values");
            }

            $processedArguments[] = self::$ttlModifierEnum[$ttlModifier];

            // KEEPTTL requires no additional value
            if ($ttlModifier !== self::TTL_KEEP_TTL) {
                if (!isset($arguments[4]) || !is_int($arguments[4])) {
                    throw new UnexpectedValueException('Modifier value is missing or incorrect type');
                }

                $processedArguments[] = $arguments[4];
            }
        }

        if (!empty($members)) {
            array_push($processedArguments, 'MEMBERS', count($members));
            $processedArguments = array_merge($processedArguments, $members);
        }

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
