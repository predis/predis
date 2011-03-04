<?php

namespace Predis\Commands;

class SetMembers extends Command {
    public function getId() { return 'SMEMBERS'; }
}
