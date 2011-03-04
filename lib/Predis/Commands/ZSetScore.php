<?php

namespace Predis\Commands;

class ZSetScore extends Command {
    public function getId() { return 'ZSCORE'; }
}
