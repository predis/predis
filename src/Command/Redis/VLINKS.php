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

class VLINKS extends RedisCommand
{
    /**
     * @var bool
     */
    private $withScores = false;

    /**
     * @return string
     */
    public function getId()
    {
        return 'VLINKS';
    }

    /**
     * @param  array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $lastArg = array_pop($arguments);

        if (is_bool($lastArg)) {
            $this->withScores = $lastArg;
            $arguments[] = 'WITHSCORES';
        } else {
            $arguments[] = $lastArg;
        }

        parent::setArguments($arguments);
    }

    /**
     * @param             $data
     * @return array|null
     */
    public function parseResponse($data): ?array
    {
        if (!is_null($data)) {
            if ($this->withScores) {
                foreach ($data as $key => $value) {
                    $data[$key] = CommandUtility::arrayToDictionary($value, function ($key, $value) {
                        return [$key, (float) $value];
                    });
                }
            }
        }

        return $data;
    }
}
