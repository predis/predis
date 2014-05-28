<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @link http://redis.io/commands/pfmerge
 */
class HyperLogLogMerge extends AbstractCommand implements PrefixableCommandInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'PFMERGE';
    }

    /**
     * {@inheritdoc}
     */
    public function prefixKeys($prefix)
    {
        PrefixHelpers::all($this, $prefix);
    }

    /**
     * {@inheritdoc}
     */
    protected function filterArguments(Array $arguments)
    {
        return self::normalizeVariadic($arguments);
    }
}
