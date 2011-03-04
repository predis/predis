<?php

namespace Predis\Commands;

class ListPopLastPushHeadBlocking extends Command {
    public function getCommandId() { return 'BRPOPLPUSH'; }
}
