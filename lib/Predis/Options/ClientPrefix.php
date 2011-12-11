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

/**
 * Option class that handles the prefixing of keys in commands.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ClientPrefix extends Option
{
    /**
     * {@inheritdoc}
     */
    public function filter(IClientOptions $options, $value)
    {
        return new KeyPrefixProcessor($value);
    }
}
