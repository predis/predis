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

use InvalidArgumentException;
use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see http://redis.io/commands/bitop
 */
class BITOP extends RedisCommand
{
    private const VALID_OPERATIONS = ['AND', 'OR', 'XOR', 'NOT', 'DIFF', 'DIFF1', 'ANDOR', 'ONE'];

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BITOP';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (count($arguments) === 3 && is_array($arguments[2])) {
            [$operation, $destination] = $arguments;
            $arguments = $arguments[2];
            array_unshift($arguments, $operation, $destination);
        }

        if (!empty($arguments)) {
            $operation = strtoupper($arguments[0]);
            if (!in_array($operation, self::VALID_OPERATIONS, false)) {
                throw new InvalidArgumentException('BITOP operation must be one of: AND, OR, XOR, NOT, DIFF, DIFF1, ANDOR, ONE');
            }
        }

        parent::setArguments($arguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixSkippingFirstArgument($prefix);
    }
}
