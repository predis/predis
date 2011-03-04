<?php

namespace Predis\Commands;

class ListPopLastPushHeadBlocking extends Command {
    public function getId() { return 'BRPOPLPUSH'; }
}
