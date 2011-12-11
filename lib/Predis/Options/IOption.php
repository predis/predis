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

/**
 * Interface that defines a client option.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IOption
{
    /**
     * Filters (and optionally converts) the passed value.
     *
     * @param mixed $value Input value.
     * @return mixed
     */
    public function filter(IClientOptions $options, $value);

    /**
     * Returns a default value for the option.
     *
     * @param mixed $value Input value.
     * @return mixed
     */
    public function getDefault(IClientOptions $options);

    /**
     * Filters a value and, if no value is specified, returns
     * the default one defined by the option.
     *
     * @param mixed $value Input value.
     * @return mixed
     */
    public function __invoke(IClientOptions $options, $value);
}
