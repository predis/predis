<?php

namespace Predis\Commands;

class Quit extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'QUIT'; }
}
