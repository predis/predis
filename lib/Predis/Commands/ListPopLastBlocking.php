<?php

namespace Predis\Commands;

class ListPopLastBlocking extends ListPopFirstBlocking {
    public function getId() {
        return 'BRPOP';
    }
}
