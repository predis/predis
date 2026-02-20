<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;

/**
 * Configures upstream driver information for CLIENT SETINFO.
 *
 * This option accepts a string or array of strings identifying upstream drivers
 * (e.g., 'laravel_v11.0.0' or ['laravel_v11.0.0', 'my-app_v1.0.0']) that will
 * be included in the LIB-NAME sent to Redis via CLIENT SETINFO.
 */
class DriverInfo implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return implode(';', $value);
        }

        throw new InvalidArgumentException(
            'DriverInfo option expects a string or an array of strings'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        return '';
    }
}
