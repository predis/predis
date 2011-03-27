<?php

namespace Predis\Commands;

class SetMultiplePreserve extends SetMultiple {
    public function getId() {
        return 'MSETNX';
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
