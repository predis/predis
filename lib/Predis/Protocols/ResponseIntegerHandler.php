<?php

namespace Predis\Protocols;

use Predis\Utils;
use Predis\ProtocolException;
use Predis\Network\IConnectionComposable;

class ResponseIntegerHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $number) {
        if (is_numeric($number)) {
            return (int) $number;
        }
        if ($number !== 'nil') {
            Utils::onCommunicationException(new ProtocolException(
                $connection, "Cannot parse '$number' as numeric response"
            ));
        }
        return null;
    }
}
