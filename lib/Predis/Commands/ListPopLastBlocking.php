<?php

namespace Predis\Commands;

class ListPopLastBlocking extends Command {
    public function getId() { return 'BRPOP'; }
}
