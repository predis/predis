<?php

namespace Predis\Commands;

class ListPushHeadX extends Command {
    public function getCommandId() { return 'LPUSHX'; }
}
