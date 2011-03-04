<?php

namespace Predis\Commands;

class ListInsert extends Command {
    public function getCommandId() { return 'LINSERT'; }
}
