<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Options;

/**
 * Implements a client option.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Option implements IOption
{
    /**
     * {@inheritdoc}
     */
    public function validate($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($value)
    {
        if (isset($value)) {
            return $this->validate($value);
        }

        return $this->getDefault();
    }
}
