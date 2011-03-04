<?php

namespace Predis\Commands;

class Quit extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}
