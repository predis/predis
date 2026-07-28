<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;
use Predis\Himport\FieldsetRegistry;
use Predis\Himport\HimportOptions;

/**
 * Configures HIMPORT support for a client: the shared fieldset registry and
 * whether the himport container auto-recovers from "no such fieldset" by
 * re-preparing and retrying.
 *
 * Accepts a Predis\Himport\HimportOptions instance, or an array with an optional
 * `auto_prepare` boolean (default true).
 */
class Himport implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if ($value instanceof HimportOptions) {
            return $value;
        }

        if (is_array($value)) {
            $autoPrepare = array_key_exists('auto_prepare', $value)
                ? (bool) $value['auto_prepare']
                : true;

            return new HimportOptions(new FieldsetRegistry(), $autoPrepare);
        }

        throw new InvalidArgumentException(
            'Invalid value for the himport option: expected an array or a Predis\Himport\HimportOptions instance.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return new HimportOptions(new FieldsetRegistry(), true);
    }
}
