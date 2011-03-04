<?php

namespace Predis\Commands;

class Config extends Command {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'CONFIG'; }
}
