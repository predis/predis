<?php

namespace Predis;

class ServerException extends PredisException {
    // Server-side errors
    public function toResponseError() {
        return new ResponseError($this->getMessage());
    }
}
