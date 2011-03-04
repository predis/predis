<?php

namespace Predis\Commands;

class ZSetIntersectionStore extends ZSetUnionStore {
    public function getCommandId() { return 'ZINTERSTORE'; }
}
