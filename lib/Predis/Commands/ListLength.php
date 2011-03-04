<?php

namespace Predis\Commands;

class ListLength extends Command {
    public function getId() { return 'LLEN'; }
}
