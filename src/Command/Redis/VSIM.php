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
use Predis\Command\Redis\Utils\CommandUtility;

class VSIM extends RedisCommand
{
    private $withScores = false;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'VSIM';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        if (isset($arguments[1]) && !is_array($arguments[1])) {
            if (isset($arguments[2]) && false !== $arguments[2]) {
                array_push($processedArguments, 'ELE', $arguments[1]);
            } else {
                array_push($processedArguments, 'FP32', $arguments[1]);
            }
        } else {
            array_push($processedArguments, 'VALUES', count($arguments[1]), ...$arguments[1]);
        }

        if (isset($arguments[3]) && false !== $arguments[3]) {
            $this->withScores = true;
            $processedArguments[] = 'WITHSCORES';
        }

        if (isset($arguments[4])) {
            array_push($processedArguments, 'COUNT', $arguments[4]);
        }

        if (isset($arguments[5])) {
            array_push($processedArguments, 'EPSILON', $arguments[5]);
        }

        if (isset($arguments[6])) {
            array_push($processedArguments, 'EF', $arguments[6]);
        }

        if (isset($arguments[7])) {
            array_push($processedArguments, 'FILTER', $arguments[7]);
        }

        if (isset($arguments[8])) {
            array_push($processedArguments, 'FILTER-EF', $arguments[8]);
        }

        if (isset($arguments[9]) && false !== $arguments[9]) {
            $processedArguments[] = 'TRUTH';
        }

        if (isset($arguments[10]) && false !== $arguments[10]) {
            $processedArguments[] = 'NOTHREAD';
        }

        parent::setArguments($processedArguments);
    }

    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parseResponse($data)
    {
        if ($this->withScores) {
            $data = CommandUtility::arrayToDictionary($data, function ($key, $value) {
                return [$key, (float) $value];
            });
        }

        return $data;
    }
}
