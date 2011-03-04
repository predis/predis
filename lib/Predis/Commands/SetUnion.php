<?php

namespace Predis\Commands;

class SetUnion extends SetIntersection {
    public function getId() { return 'SUNION'; }
}
