<?php

namespace Predis\Commands;

class ListPushHead extends ListPushTail {
    public function getId() {
        return 'LPUSH';
    }
}
