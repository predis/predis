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

class ResponseError implements IRedisServerError
{
    private $_message;

    public function __construct($message)
    {
        $this->_message = $message;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function getErrorType()
    {
        list($errorType, ) = explode(' ', $this->getMessage(), 2);
        return $errorType;
    }

    public function __toString()
    {
        return $this->getMessage();
    }
}
