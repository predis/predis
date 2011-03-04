<?php

namespace Predis\Commands;

class SetPop  extends Command {
    public function getCommandId() { return 'SPOP'; }
}
