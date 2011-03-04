<?php

namespace Predis\Commands;

class SetRandomMember extends Command {
    public function getId() { return 'SRANDMEMBER'; }
}
