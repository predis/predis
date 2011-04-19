<?php

namespace Predis\Commands;

class ListPushHeadV24x extends ListPushTailV24x {
    public function getId() {
        return 'LPUSH';
    }
}
