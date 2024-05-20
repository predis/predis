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

class HSetFArguments extends CommonExpiration
{
    /**
     * @var array
     */
    protected $fieldModifierEnum = [
        'DCF', 'DOF',
    ];

    /**
     * @var array
     */
    protected $getModifierEnum = [
        'GETNEW', 'GETOLD',
    ];

    /**
     * Restricts to create a key if given key does not exist.
     *
     * @return $this
     */
    public function setDontCreate(): self
    {
        $this->arguments[] = 'DC';

        return $this;
    }

    /**
     * Set the modifier that define a behaviour on already existing/non-existing fields.
     *
     * DCF for each specified field if the field already exists set the field's value and expiration time ignore fields that do not exist
     *
     * DOF for each specified field if such field does not exist create field and set its value and expiration time ignore fields that already exists
     *
     * @param  string $modifier
     * @return $this
     */
    public function setFieldModifier(string $modifier): self
    {
        if (!in_array(strtoupper($modifier), $this->fieldModifierEnum, true)) {
            throw new UnexpectedValueException('Incorrect field modifier value');
        }

        if (!empty(array_intersect($this->fieldModifierEnum, $this->arguments))) {
            throw new UnexpectedValueException('Cannot be mixed with other field modifiers');
        }

        $this->arguments[] = strtoupper($modifier);

        return $this;
    }

    /**
     * Set the modifier that define a return value to be old/new field value.
     *
     * GETNEW new value of field or null if field does not exist and DCF specified.
     *
     * GETOLD old value of field or null if no such field existed before the command execution.
     *
     * @param  string $modifier
     * @return $this
     */
    public function setGetModifier(string $modifier): self
    {
        if (!in_array(strtoupper($modifier), $this->getModifierEnum, true)) {
            throw new UnexpectedValueException('Incorrect get modifier value');
        }

        if (!empty(array_intersect($this->getModifierEnum, $this->arguments))) {
            throw new UnexpectedValueException('Cannot be mixed with other GET modifiers');
        }

        $this->arguments[] = strtoupper($modifier);

        return $this;
    }

    /**
     * For each specified field retain the previous expiration time.
     *
     * @return $this
     */
    public function enableKeepTTL(): self
    {
        if (!empty(array_intersect($this->ttlModifierEnum, $this->arguments))) {
            throw new UnexpectedValueException('Keep TTL argument cannot be mixed with one of TTL modifiers');
        }

        $this->arguments[] = 'KEEPTTL';

        return $this;
    }
}
