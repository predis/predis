<?php

namespace Predis\Commands;

class Publish extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'PUBLISH'; }
}
