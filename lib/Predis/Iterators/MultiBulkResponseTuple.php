<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Iterators;

class MultiBulkResponseTuple extends MultiBulkResponse
{
    private $_iterator;

    public function __construct(MultiBulkResponseSimple $iterator)
    {
        $virtualSize = count($iterator) / 2;
        $this->_iterator = $iterator;
        $this->_position = 0;
        $this->_current = $virtualSize > 0 ? $this->getValue() : null;
        $this->_replySize = $virtualSize;
    }

    public function __destruct()
    {
        $this->_iterator->sync();
    }

    protected function getValue()
    {
        $k = $this->_iterator->current();
        $this->_iterator->next();

        $v = $this->_iterator->current();
        $this->_iterator->next();

        return array($k, $v);
    }
}
