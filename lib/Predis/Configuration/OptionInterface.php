<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration;

/**
 * Defines an handler used by Predis\Configuration\Options to
 * filter, validate or get default values for a given option.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface OptionInterface
{
    /**
     * Filters and validates the passed value.
     *
     * @param mixed $value Input value.
     * @return mixed
     */
    public function filter(OptionsInterface $options, $value);

    /**
     * Returns the default value for the option.
     *
     * @param mixed $value Input value.
     * @return mixed
     */
    public function getDefault(OptionsInterface $options);
}
