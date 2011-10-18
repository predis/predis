<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Network;

use Predis\Protocol\IProtocolProcessor;

interface IConnectionComposable extends IConnectionSingle
{
    public function setProtocol(IProtocolProcessor $protocol);
    public function getProtocol();
    public function writeBytes($buffer);
    public function readBytes($length);
    public function readLine();
}
