<?php

namespace Predis\Commands;

class ListPopLastBlocking extends Command {
    public function getCommandId() { return 'BRPOP'; }
}
