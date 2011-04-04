<?php

namespace Predis;

use Predis\Network\IConnectionSingle;

class ProtocolException extends CommunicationException {
    // Unexpected responses

    public function __construct(IConnectionSingle $connection, $message = null) {
        parent::__construct($connection, $message);
    }
}
