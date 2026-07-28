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
 * Configures HIMPORT support for a client.
 *
 * Accepts a Predis\Himport\HimportOptions instance, or an array with:
 *   - `fieldsets`:    optional map of fieldset name => ordered, non-empty field
 *                     list. Pre-declared fieldsets are prepared on demand the
 *                     first time a `HIMPORT SET` references them on a connection,
 *                     so the application does not need to call `prepare()` for
 *                     them (this uses the same auto-prepare mechanism below).
 *   - `auto_prepare`: optional bool (default true) toggling whether the himport
 *                     container prepares/re-prepares fieldsets on demand and
 *                     recovers from "no such fieldset".
 *
 * @experimental This option is experimental and its shape may change in a future release.
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

        if (!is_array($value)) {
            throw new InvalidArgumentException(
                'Invalid value for the himport option: expected an array or a Predis\Himport\HimportOptions instance.'
            );
        }

        $autoPrepare = array_key_exists('auto_prepare', $value)
            ? (bool) $value['auto_prepare']
            : true;

        $registry = null;

        if (array_key_exists('fieldsets', $value)) {
            $registry = $this->buildRegistry($value['fieldsets']);
        }

        return new HimportOptions($registry, $autoPrepare);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return new HimportOptions(null, true);
    }

    /**
     * Builds a fieldset registry pre-seeded from the `fieldsets` option.
     *
     * @param  mixed            $fieldsets
     * @return FieldsetRegistry
     */
    private function buildRegistry($fieldsets): FieldsetRegistry
    {
        if (!is_array($fieldsets)) {
            throw new InvalidArgumentException(
                'The "fieldsets" himport option must be a map of fieldset name => list of field names.'
            );
        }

        $registry = new FieldsetRegistry();

        foreach ($fieldsets as $name => $fields) {
            if (!is_array($fields) || empty($fields)) {
                throw new InvalidArgumentException(
                    sprintf('Fieldset "%s" must be a non-empty list of field names.', $name)
                );
            }

            $registry->set((string) $name, array_values($fields));
        }

        return $registry;
    }
}
