<?php

namespace Predis\Network;

use Predis\Protocols\IRedisProtocol;

interface IConnectionComposable extends IConnectionSingle {
    public function setProtocol(IRedisProtocol $protocol);
    public function getProtocol();
    public function writeBytes($buffer);
    public function readBytes($length);
    public function readLine();
}
