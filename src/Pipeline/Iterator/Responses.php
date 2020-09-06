<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline\Iterator;

use NoRewindIterator;

/**
 * Non-rewindable iterator for pipeline results.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Responses extends NoRewindIterator
{
    /**
     * Returns pipeline results as an array.
     *
     * Since pipeline results are non-rewindable, invoking this method after the
     * start of an iteration will just return the reminder of the results set.
     *
     * @return array
     */
    public function all(): array
    {
        return iterator_to_array($this, false);
    }
}
