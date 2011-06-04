<?php

namespace Predis\Protocol\Text;

use Predis\ResponseQueued;
use Predis\Protocol\IResponseHandler;
use Predis\Network\IConnectionComposable;

class ResponseStatusHandler implements IResponseHandler {
    public function handle(IConnectionComposable $connection, $status) {
        switch ($status) {
            case 'OK':
                return true;
            case 'QUEUED':
                return new ResponseQueued();
            default:
                return $status;
        }
    }
}
