<?php

namespace Predis\Protocol\Text;

use Predis\Helpers;
use Predis\Protocol\IResponseHandler;
use Predis\Protocol\ProtocolException;
use Predis\Network\IConnectionComposable;
use Predis\Iterators\MultiBulkResponseSimple;

class ResponseMultiBulkStreamHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $lengthString) {
        $length = (int) $lengthString;
        if ($length != $lengthString) {
            Helpers::onCommunicationException(new ProtocolException(
                $connection, "Cannot parse '$length' as data length"
            ));
        }
        return new MultiBulkResponseSimple($connection, $length);
    }
}
