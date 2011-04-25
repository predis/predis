<?php

namespace Predis\Commands;

class StringSetMultiplePreserve extends StringSetMultiple {
    public function getId() {
        return 'MSETNX';
    }

    public function parseResponse($data) {
        return (bool) $data;
    }
}
