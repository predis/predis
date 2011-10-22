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

/**
 * Abstracts the access to a streamable list of tuples represented
 * as a multibulk reply that alternates keys and values.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MultiBulkResponseTuple extends MultiBulkResponse
{
    private $_iterator;

    /**
     * @param MultiBulkResponseSimple $iterator Multibulk reply iterator.
     */
    public function __construct(MultiBulkResponseSimple $iterator)
    {
        $virtualSize = count($iterator) / 2;
        $this->_iterator = $iterator;
        $this->_position = 0;
        $this->_current = $virtualSize > 0 ? $this->getValue() : null;
        $this->_replySize = $virtualSize;
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        $this->_iterator->sync();
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue()
    {
        $k = $this->_iterator->current();
        $this->_iterator->next();

        $v = $this->_iterator->current();
        $this->_iterator->next();

        return array($k, $v);
    }
}
