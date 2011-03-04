<?php

namespace Predis\Commands;

class ListTrim extends Command {
    public function getCommandId() { return 'LTRIM'; }
}
