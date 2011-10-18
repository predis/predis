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

class Option implements IOption
{
    public function validate($value)
    {
        return $value;
    }

    public function getDefault()
    {
        return null;
    }

    public function __invoke($value)
    {
        if (isset($value)) {
            return $this->validate($value);
        }

        return $this->getDefault();
    }
}
