<?php

namespace Predis\Commands;

class ListPushTailX extends Command {
    public function getCommandId() { return 'RPUSHX'; }
}
