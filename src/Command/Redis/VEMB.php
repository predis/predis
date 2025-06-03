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

class VEMB extends RedisCommand
{
    /**
     * @var bool
     */
    private $isRaw = false;

    /**
     * @return string
     */
    public function getId()
    {
        return 'VEMB';
    }

    /**
     * @param  array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0], $arguments[1]];

        if (isset($arguments[2])) {
            $this->isRaw = true;
            $processedArguments[] = 'RAW';
        }

        parent::setArguments($processedArguments);
    }

    /**
     * @param                            $data
     * @return array|float[]|string|null
     */
    public function parseResponse($data)
    {
        if (!$this->isRaw) {
            return array_map(function ($value) { return (float) $value; }, $data);
        }

        $parsedData = [];

        for ($i = 0; $i < count($data); $i++) {
            if ($i > 1) {
                $parsedData[] = (float) $data[$i];
            } else {
                $parsedData[] = $data[$i];
            }
        }

        return $parsedData;
    }
}
