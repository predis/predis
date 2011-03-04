<?php

namespace Predis\Commands;

class ListPushTailX extends Command {
    public function getId() { return 'RPUSHX'; }
}
