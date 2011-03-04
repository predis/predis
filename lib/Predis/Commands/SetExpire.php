<?php

namespace Predis\Commands;

class SetExpire extends Command {
    public function getId() { return 'SETEX'; }
}
