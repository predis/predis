<?php

namespace Predis\Commands;

class SetUnionStore extends SetIntersectionStore {
    public function getCommandId() { return 'SUNIONSTORE'; }
}
