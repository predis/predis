<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Hash;

use UnexpectedValueException;

class HGetFArguments extends CommonExpiration
{
    /**
     * Removes current expiration time from given fields.
     *
     * @return $this
     */
    public function setPersist(): self
    {
        if (!empty(array_intersect($this->ttlModifierEnum, $this->arguments))) {
            throw new UnexpectedValueException('PERSIST argument cannot be mixed with one of TTL modifiers');
        }

        $this->arguments[] = 'PERSIST';

        return $this;
    }
}
