<?php

namespace Predis\Commands;

class ZSetReverseRange extends ZSetRange {
    public function getCommandId() { return 'ZREVRANGE'; }
}
