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

use Predis\Command\PrefixableCommand;
use ValueError;

class MSETEX extends PrefixableCommand
{
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return 'MSETEX';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [count(array_keys($arguments[0]))];

        array_walk($arguments[0], function ($value, $key) use (&$processedArguments) {
            array_push($processedArguments, $key, $value);
        });

        if (isset($arguments[1])) {
            if (!in_array(strtoupper($arguments[1]), ['NX', 'XX'])) {
                throw new ValueError('Incorrect exist modifier. Should be one of: NX, XX.');
            }

            $processedArguments[] = strtoupper($arguments[1]);
        }

        if (isset($arguments[2])) {
            if (!isset($arguments[3]) && strtoupper($arguments[2]) !== 'KEEPTTL') {
                throw new ValueError('TTL should be specified along with expire resolution parameter');
            }

            if (!in_array(strtoupper($arguments[2]), ['EX', 'PX', 'EXAT', 'PXAT', 'KEEPTTL'])) {
                throw new ValueError('Incorrect expire modifier. Should be one of: EX, PX, EXAT, PXAT, KEEPTTL');
            }

            if (strtoupper($arguments[2]) !== 'KEEPTTL') {
                array_push($processedArguments, strtoupper($arguments[2]), $arguments[3]);
            } else {
                $processedArguments[] = strtoupper($arguments[2]);
            }
        }

        parent::setArguments($processedArguments);
    }

    /**
     * {@inheritDoc}
     */
    public function prefixKeys($prefix)
    {
        $arguments = $this->getArguments();
        $keysCount = $arguments[0];
        $currentKeyIndex = 1;

        while ($keysCount > 0) {
            $arguments[$currentKeyIndex] = $prefix . $arguments[$currentKeyIndex];
            $keysCount--;
            $currentKeyIndex += 2;
        }

        parent::setRawArguments($arguments);
    }
}
