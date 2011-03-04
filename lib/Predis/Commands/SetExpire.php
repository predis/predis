<?php

namespace Predis\Commands;

class SetExpire extends Command {
    public function getCommandId() { return 'SETEX'; }
}
