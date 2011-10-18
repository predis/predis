<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

/**
 * Interface that must be implemented by classes that provide their own mechanism
 * to create and initialize new instances of Predis\Network\IConnectionSingle.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IConnectionFactory
{
    /**
     * Creates a new connection object.
     *
     * @param mixed $parameters Parameters for the connection.
     * @return Predis\Network\IConnectionSingle
     */
    public function create($parameters);
}
