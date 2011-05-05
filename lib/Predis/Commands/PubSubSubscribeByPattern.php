<?php

namespace Predis\Commands;

use Predis\Helpers;

class PubSubSubscribeByPattern extends PubSubSubscribe {
    public function getId() {
        return 'PSUBSCRIBE';
    }
}
