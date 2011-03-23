<?php

namespace Predis\Commands;

class ZSetIntersectionStore extends ZSetUnionStore {
    protected function canBeHashed() { return false; }
    public function getId() { return 'ZINTERSTORE'; }
}
