<?php

namespace Predis\Commands;

class GetBit extends Command {
    public function getCommandId() { return 'GETBIT'; }
}
