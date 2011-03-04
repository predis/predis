<?php

namespace Predis\Commands;

class ListLength extends Command {
    public function getCommandId() { return 'LLEN'; }
}
