<?php

namespace Predis\Commands;

class ListPopLast extends Command {
    public function getId() { return 'RPOP'; }
}
