<?php

namespace Predis\Commands;

class ListPopFirstBlocking extends Command {
    public function getCommandId() { return 'BLPOP'; }
}
