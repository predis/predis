<?php

namespace Predis\Commands;

class ListPushTail extends Command {
    public function getCommandId() { return 'RPUSH'; }
}
