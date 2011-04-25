<?php

namespace Predis\Commands;

class KeyRename extends Command {
    public function getId() {
        return 'RENAME';
    }

    protected function canBeHashed() {
        return false;
    }
}
