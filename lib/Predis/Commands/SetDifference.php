<?php

namespace Predis\Commands;

class SetDifference extends SetIntersection {
    public function getCommandId() { return 'SDIFF'; }
}
