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
use ValueError;

class HOTKEYS extends RedisCommand
{
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return 'HOTKEYS';
    }

    /**
     * @param  array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        switch ($arguments[0]) {
            case 'START':
                $this->setStartArguments($arguments);
                break;

            default:
                parent::setArguments($arguments);
        }
    }

    public function parseResponse($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                $dict = CommandUtility::arrayToDictionary($item, null, false);
                $data[$key] = $dict;
            }
        }

        return $data;
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setStartArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        array_push($processedArguments, 'METRICS', count($arguments[1]), ...$arguments[1]);

        if (isset($arguments[2])) {
            if ($arguments[2] > 9 && $arguments[2] < 65) {
                array_push($processedArguments, 'COUNT', $arguments[2]);
            } else {
                throw new ValueError('Count value should be between 10 and 64');
            }
        }

        if (isset($arguments[3])) {
            array_push($processedArguments, 'DURATION', $arguments[3]);
        }

        if (isset($arguments[4])) {
            if ($arguments[4] > 0) {
                array_push($processedArguments, 'SAMPLE', $arguments[4]);
            } else {
                throw new ValueError('Sample value should be greater than 0');
            }
        }

        if (isset($arguments[5])) {
            array_push($processedArguments, 'SLOTS', count($arguments[5]), ...$arguments[5]);
        }

        parent::setArguments($processedArguments);
    }
}
