<?php

namespace Predis\Commands;

class ListSet extends Command {
    public function getCommandId() { return 'LSET'; }
}
