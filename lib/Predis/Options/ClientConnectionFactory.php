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

use Predis\IConnectionFactory;
use Predis\ConnectionFactory;

class ClientConnectionFactory extends Option
{
    public function validate($value)
    {
        if ($value instanceof IConnectionFactory) {
            return $value;
        }
        if (is_array($value)) {
            return new ConnectionFactory($value);
        }
    }

    public function getDefault()
    {
        return new ConnectionFactory();
    }
}
