<?php

namespace Predis\Protocols\Text;

use Predis\Helpers;
use Predis\ProtocolException;
use Predis\Protocols\IResponseHandler;
use Predis\Network\IConnectionComposable;

class ResponseBulkHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $lengthString) {
        $length = (int) $lengthString;
        if ($length != $lengthString) {
            Helpers::onCommunicationException(new ProtocolException(
                $connection, "Cannot parse '$length' as data length"
            ));
        }
        if ($length >= 0) {
            return substr($connection->readBytes($length + 2), 0, -2);
        }
        if ($length == -1) {
            return null;
        }
    }
}
