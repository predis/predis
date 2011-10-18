<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol;

interface IComposableProtocolProcessor extends IProtocolProcessor
{
    public function setSerializer(ICommandSerializer $serializer);
    public function getSerializer();
    public function setReader(IResponseReader $reader);
    public function getReader();
}
