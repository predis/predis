<?php

namespace Predis\Commands;

class ListPushHead extends Command {
    public function getId() { return 'LPUSH'; }
}
