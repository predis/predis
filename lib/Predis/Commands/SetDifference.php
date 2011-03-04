<?php

namespace Predis\Commands;

class SetDifference extends SetIntersection {
    public function getId() { return 'SDIFF'; }
}
