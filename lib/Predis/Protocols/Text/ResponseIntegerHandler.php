<?php

namespace Predis\Protocols\Text;

use Predis\Helpers;
use Predis\ProtocolException;
use Predis\Protocols\IResponseHandler;
use Predis\Network\IConnectionComposable;

class ResponseIntegerHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $number) {
        if (is_numeric($number)) {
            return (int) $number;
        }
        if ($number !== 'nil') {
            Helpers::onCommunicationException(new ProtocolException(
                $connection, "Cannot parse '$number' as numeric response"
            ));
        }
        return null;
    }
}
