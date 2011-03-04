<?php

namespace Predis\Commands;

class SetDifferenceStore extends SetIntersectionStore {
    public function getCommandId() { return 'SDIFFSTORE'; }
}
