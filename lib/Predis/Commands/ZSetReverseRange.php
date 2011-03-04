<?php

namespace Predis\Commands;

class ZSetReverseRange extends ZSetRange {
    public function getId() { return 'ZREVRANGE'; }
}
