<?php

namespace Predis\Commands;

class SelectDatabase extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'SELECT'; }
}
