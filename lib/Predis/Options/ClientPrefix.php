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

use Predis\Commands\Processors\KeyPrefixProcessor;

class ClientPrefix extends Option
{
    public function validate($value)
    {
        return new KeyPrefixProcessor($value);
    }
}
