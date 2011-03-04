<?php

namespace Predis\Commands;

class SetDifferenceStore extends SetIntersectionStore {
    public function getId() { return 'SDIFFSTORE'; }
}
