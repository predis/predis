<?php

namespace Predis\Protocols;

interface IProtocolProcessorExtended extends IProtocolProcessor {
    public function setSerializer(ICommandSerializer $serializer);
    public function getSerializer();
    public function setReader(IResponseReader $reader);
    public function getReader();
}
