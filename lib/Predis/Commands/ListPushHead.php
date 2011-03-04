<?php

namespace Predis\Commands;

class ListPushHead extends Command {
    public function getCommandId() { return 'LPUSH'; }
}
