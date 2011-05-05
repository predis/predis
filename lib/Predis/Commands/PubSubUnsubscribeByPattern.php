<?php

namespace Predis\Commands;

class PubSubUnsubscribeByPattern extends PubSubUnsubscribe {
    public function getId() {
        return 'PUNSUBSCRIBE';
    }
}
