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

use Predis\Command\Argument\ArrayableArgument;
use UnexpectedValueException;

abstract class CommonExpiration implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $overrideModifierEnum = [
        'NX', 'XX', 'GT', 'LT',
    ];

    /**
     * @var array
     */
    protected $ttlModifierEnum = [
        'EX', 'PX', 'EXAT', 'PXAT',
    ];

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Set the modifier that defines a behaviour on expiration.
     *
     * NX for each specified field set expiration only when the field has no expiration.
     *
     * XX for each specified field set expiration only when the field has an existing expiration.
     *
     * GT for each specified field set expiration only when the new expiration time is greater than the field's current one.
     * A field with no expiration is treated as an infinite expiration.
     *
     * LT for each specified field set expiration only when the new expiration time is less than the field's current one.
     * A field with no expiration is treated as an infinite expiration.
     *
     * @param  string $modifier
     * @return $this
     */
    public function setOverrideModifier(string $modifier): self
    {
        if (!in_array(strtoupper($modifier), $this->overrideModifierEnum, true)) {
            throw new UnexpectedValueException('Incorrect expire modifier value');
        }

        if (!empty(array_intersect($this->overrideModifierEnum, $this->arguments))) {
            throw new UnexpectedValueException('Cannot be mixed with other override modifiers');
        }

        $this->arguments[] = strtoupper($modifier);

        return $this;
    }

    /**
     * Set the TTL for each specified field.
     *
     * EX seconds for each specified field set the remaining time to live in seconds.
     *
     * PX milliseconds for each specified field set the remaining time to live in milliseconds.
     *
     * EXAT unix-time-seconds for each specified field set the expiration time to a UNIX timestamp specified in seconds since the Unix epoch.
     *
     * PXAT unix-time-milliseconds for each specified field set the expiration time to a UNIX timestamp specified in
     * milliseconds since the Unix epoch.
     *
     * @param  string $modifier
     * @param  int    $value
     * @return $this
     */
    public function setTTLModifier(string $modifier, int $value): self
    {
        if (!in_array(strtoupper($modifier), $this->ttlModifierEnum, true)) {
            throw new UnexpectedValueException('Incorrect TTL modifier');
        }

        $allModifiers = $this->ttlModifierEnum + ['KEEPTTL', 'PERSIST'];

        if (!empty(array_intersect($allModifiers, $this->arguments))) {
            throw new UnexpectedValueException('Cannot be mixed with other TTL modifiers');
        }

        $this->arguments[] = strtoupper($modifier);
        $this->arguments[] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
