<?php

namespace Predis\Protocols;

use Predis\Network\IConnectionSingle;

class ResponseStatusHandler implements IResponseHandler {
    public function handle(IConnectionSingle $connection, $status) {
        if ($status === 'OK') {
            return true;
        }
        if ($status === 'QUEUED') {
            return new \Predis\ResponseQueued();
        }
        return $status;
    }
}
