<?php

namespace Predis\Commands;

class Decrement extends Command {
    public function getId() { return 'DECR'; }
}
