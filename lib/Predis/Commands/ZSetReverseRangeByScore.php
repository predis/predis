<?php

namespace Predis\Commands;

class ZSetReverseRangeByScore extends ZSetRangeByScore {
    public function getId() { return 'ZREVRANGEBYSCORE'; }
}
