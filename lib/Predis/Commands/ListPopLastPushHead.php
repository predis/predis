<?php

namespace Predis\Commands;

class ListPopLastPushHead extends Command {
    public function getCommandId() { return 'RPOPLPUSH'; }
}
