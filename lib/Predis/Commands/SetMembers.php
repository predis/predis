<?php

namespace Predis\Commands;

class SetMembers extends Command {
    public function getCommandId() { return 'SMEMBERS'; }
}
