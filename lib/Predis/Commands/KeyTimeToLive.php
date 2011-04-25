<?php

namespace Predis\Commands;

class KeyTimeToLive extends Command {
    public function getId() {
        return 'TTL';
    }
}
