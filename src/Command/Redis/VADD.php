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

use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class VADD extends RedisCommand
{
    public const QUANT_DEFAULT = null;
    public const QUANT_NOQUANT = 'NOQUANT';
    public const QUANT_BIN = 'BIN';
    public const QUANT_Q8 = 'Q8';

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return 'VADD';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        if (isset($arguments[3])) {
            array_push($processedArguments, 'REDUCE', $arguments[3]);
        }

        if (is_string($arguments[1])) {
            array_push($processedArguments, 'FP32', $arguments[1]);
        } elseif (is_array($arguments[1])) {
            array_push($processedArguments, 'VALUES', count($arguments[1]), ...$arguments[1]);
        } else {
            throw new UnexpectedValueException('Vector should be rather 32 bit floating blob or array of floatings');
        }

        $processedArguments[] = $arguments[2];

        if (isset($arguments[4]) && false !== $arguments[4]) {
            $processedArguments[] = 'CAS';
        }

        if (isset($arguments[5])) {
            $processedArguments[] = $arguments[5];
        }

        if (isset($arguments[6])) {
            array_push($processedArguments, 'EF', $arguments[6]);
        }

        if (isset($arguments[7])) {
            $processedArguments[] = 'SETATTR';

            if (is_string($arguments[7])) {
                $processedArguments[] = $arguments[7];
            } elseif (is_array($arguments[7])) {
                $processedArguments[] = json_encode($arguments[7]);
            } else {
                throw new UnexpectedValueException('Attributes arguments should be a JSON string or associative array');
            }
        }

        if (isset($arguments[8])) {
            array_push($processedArguments, 'M', $arguments[8]);
        }

        parent::setArguments($processedArguments);
    }

    public function parseResponse($data): bool
    {
        return (bool) $data;
    }
}
