<?php

namespace Predis\Commands;

class ListPopFirst extends Command {
    public function getId() { return 'LPOP'; }
}
