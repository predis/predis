<?php

namespace Predis\Commands;

class ListPopFirst extends Command {
    public function getCommandId() { return 'LPOP'; }
}
