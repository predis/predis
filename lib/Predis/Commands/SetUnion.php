<?php

namespace Predis\Commands;

class SetUnion extends SetIntersection {
    public function getCommandId() { return 'SUNION'; }
}
