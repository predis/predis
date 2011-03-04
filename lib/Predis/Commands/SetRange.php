<?php

namespace Predis\Commands;

class SetRange extends Command {
    public function getCommandId() { return 'SETRANGE'; }
}
