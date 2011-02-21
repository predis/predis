<?php

namespace Predis\Protocols;

use Predis\Utils;
use Predis\CommunicationException;
use Predis\MalformedServerResponse;
use Predis\Network\IConnectionSingle;
use Predis\Iterators\MultiBulkResponseSimple;

class ResponseMultiBulkStreamHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $lengthString) {
        $length = (int) $lengthString;
        if ($length != $lengthString) {
            Utils::onCommunicationException(new MalformedServerResponse(
                $connection, "Cannot parse '$length' as data length"
            ));
        }
        return new MultiBulkResponseSimple($connection, $length);
    }
}
