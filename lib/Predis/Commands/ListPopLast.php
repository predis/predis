<?php

namespace Predis\Commands;

class ListPopLast extends Command {
    public function getCommandId() { return 'RPOP'; }
}
