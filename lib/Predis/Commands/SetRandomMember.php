<?php

namespace Predis\Commands;

class SetRandomMember extends Command {
    public function getCommandId() { return 'SRANDMEMBER'; }
}
