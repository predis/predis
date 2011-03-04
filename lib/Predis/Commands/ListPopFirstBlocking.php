<?php

namespace Predis\Commands;

class ListPopFirstBlocking extends Command {
    public function getId() { return 'BLPOP'; }
}
