<?php

namespace Predis\Commands;

class SelectDatabase extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'SELECT'; }
}
