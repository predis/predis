<?php

namespace Predis\Commands;

class ListPushHeadX extends Command {
    public function getId() { return 'LPUSHX'; }
}
