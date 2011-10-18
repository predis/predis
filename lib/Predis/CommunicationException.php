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

use Predis\Network\IConnectionSingle;

abstract class CommunicationException extends PredisException
{
    private $_connection;

    public function __construct(IConnectionSingle $connection, $message = null, $code = null, \Exception $innerException = null)
    {
        parent::__construct($message, $code, $innerException);

        $this->_connection = $connection;
    }

    public function getConnection()
    {
        return $this->_connection;
    }

    public function shouldResetConnection()
    {
        return true;
    }
}
