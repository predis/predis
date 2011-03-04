<?php

namespace Predis\Commands;

class GetRange extends Command {
    public function getCommandId() { return 'GETRANGE'; }
}
