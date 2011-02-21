<?php

namespace Predis\Protocols;

interface IResponseReader {
    public function setHandler($prefix, IResponseHandler $handler);
    public function getHandler($prefix);
}
