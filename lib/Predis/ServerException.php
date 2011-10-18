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

class ServerException extends PredisException implements IRedisServerError
{
    public function getErrorType()
    {
        list($errorType, ) = explode(' ', $this->getMessage(), 2);
        return $errorType;
    }

    public function toResponseError()
    {
        return new ResponseError($this->getMessage());
    }
}
