<?php

namespace Predis\Commands;

class ListPushTail extends Command {
    public function getId() { return 'RPUSH'; }
}
