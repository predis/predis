<?php

namespace Predis\Commands;

class KeyType extends Command {
    public function getId() {
        return 'TYPE';
    }
}
