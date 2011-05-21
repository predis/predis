<?php

namespace Predis;

interface IRedisServerError extends IReplyObject {
    public function getMessage();
    public function getErrorType();
}
