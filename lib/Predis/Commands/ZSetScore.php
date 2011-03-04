<?php

namespace Predis\Commands;

class ZSetScore extends Command {
    public function getCommandId() { return 'ZSCORE'; }
}
