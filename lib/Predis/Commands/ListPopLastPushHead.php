<?php

namespace Predis\Commands;

class ListPopLastPushHead extends Command {
    public function getId() { return 'RPOPLPUSH'; }
}
