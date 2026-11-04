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
 * @see https://redis.io/commands/sexpire/
 */
class SEXPIRE extends RedisCommand
{
    /**
     * @var string[]
     */
    protected $flagsEnum = [
        'NX', 'XX', 'GT', 'LT',
    ];

    public function getId()
    {
        return 'SEXPIRE';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0], $arguments[1] ?? null];

        if (isset($arguments[3])) {
            if (!in_array(strtoupper($arguments[3]), $this->flagsEnum, true)) {
                throw new UnexpectedValueException('Unsupported flag value');
            }

            $processedArguments[] = strtoupper($arguments[3]);
        }

        if (!empty($arguments[2])) {
            array_push($processedArguments, 'MEMBERS', count($arguments[2]));
            $processedArguments = array_merge($processedArguments, $arguments[2]);
        }

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
