<?php

namespace Predis\Protocols;

use Predis\Utils;
use Predis\CommunicationException;
use Predis\MalformedServerResponse;
use Predis\Network\IConnectionComposable;

class ResponseBulkHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $lengthString) {
        $length = (int) $lengthString;
        if ($length != $lengthString) {
            Utils::onCommunicationException(new MalformedServerResponse(
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
