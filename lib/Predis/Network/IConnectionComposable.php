<?php

namespace Predis\Network;

use Predis\Protocols\IProtocolProcessor;

interface IConnectionComposable extends IConnectionSingle {
    public function setProtocol(IProtocolProcessor $protocol);
    public function getProtocol();
    public function writeBytes($buffer);
    public function readBytes($length);
    public function readLine();
}
