<?php

namespace Predis\Commands;

class ZSetIntersectionStore extends ZSetUnionStore {
    public function getId() { return 'ZINTERSTORE'; }
}
