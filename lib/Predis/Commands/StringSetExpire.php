<?php

namespace Predis\Commands;

class StringSetExpire extends Command {
    public function getId() {
        return 'SETEX';
    }
}
