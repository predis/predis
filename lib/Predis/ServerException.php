<?php

namespace Predis;

class ServerException extends PredisException implements IRedisServerError {
    public function getErrorType() {
        list($errorType, ) = explode(' ', $this->getMessage(), 2);
        return $errorType;
    }

    public function toResponseError() {
        return new ResponseError($this->getMessage());
    }
}
