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
 * @see http://redis.io/commands/xnack
 */
class XNACK extends RedisCommand
{
    public const SILENT = 'silent';
    public const FAIL = 'fail';
    public const FATAL = 'fatal';

    /**
     * @var string[]
     */
    private static $modeEnum = [
        self::SILENT => 'SILENT',
        self::FAIL => 'FAIL',
        self::FATAL => 'FATAL',
    ];

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XNACK';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        // arguments: [key, group, mode, ids, retryCount?, force?]
        if (!in_array(strtoupper($arguments[2]), self::$modeEnum, true)) {
            $enumValues = implode(', ', array_keys(self::$modeEnum));
            throw new UnexpectedValueException("Mode argument accepts only: {$enumValues} values");
        }

        $processedArguments = [
            $arguments[0],
            $arguments[1],
            strtoupper($arguments[2]),
        ];

        array_push($processedArguments, 'IDS', strval(count($arguments[3])), ...$arguments[3]);

        if (isset($arguments[4]) && $arguments[4] !== null) {
            array_push($processedArguments, 'RETRYCOUNT', $arguments[4]);
        }

        if (!empty($arguments[5])) {
            $processedArguments[] = 'FORCE';
        }

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
