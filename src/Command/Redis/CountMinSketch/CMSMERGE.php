<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/cms.merge/
 *
 * Merges several sketches into one sketch.
 * All sketches must have identical width and depth.
 * Weights can be used to multiply certain sketches. Default weight is 1.
 */
class CMSMERGE extends RedisCommand
{
    public function getId()
    {
        return 'CMS.MERGE';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = array_merge([$arguments[0], count($arguments[1])], $arguments[1]);

        if (!empty($arguments[2])) {
            $processedArguments[] = 'WEIGHTS';
            $processedArguments = array_merge($processedArguments, $arguments[2]);
        }

        parent::setArguments($processedArguments);
    }
}
