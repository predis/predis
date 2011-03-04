<?php

namespace Predis\Commands;

use Predis\Iterators\MultiBulkResponseTuple;

class HashGetAll extends Command {
    public function getId() { return 'HGETALL'; }
    public function parseResponse($data) {
        if ($data instanceof \Iterator) {
            return new MultiBulkResponseTuple($data);
        }
        $result = array();
        for ($i = 0; $i < count($data); $i++) {
            $result[$data[$i]] = $data[++$i];
        }
        return $result;
    }
}
