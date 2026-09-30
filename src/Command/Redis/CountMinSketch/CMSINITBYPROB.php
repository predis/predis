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

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/cms.initbyprob/
 *
 * Initializes a Count-Min Sketch to accommodate requested tolerances.
 */
class CMSINITBYPROB extends RedisCommand
{
    public function getId()
    {
        return 'CMS.INITBYPROB';
    }

    /**
     * {@inheritdoc}
     *
     * Arguments: [key, errorRate, probability, ?cellSize]
     */
    public function setArguments(array $arguments)
    {
        if (array_key_exists(3, $arguments)) {
            $cellSize = $arguments[3];
            $arguments = array_slice($arguments, 0, 3);

            if ($cellSize !== null) {
                $arguments[] = 'CELL_SIZE';
                $arguments[] = $cellSize;
            }
        }

        parent::setArguments($arguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
