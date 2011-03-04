<?php

namespace Predis\Commands;

class SetUnionStore extends SetIntersectionStore {
    public function getId() { return 'SUNIONSTORE'; }
}
