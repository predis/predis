<?php

namespace Predis\Commands;

use Predis\Iterators\MultiBulkResponseTuple;

class ZSetRange extends Command {
    public function getId() {
        return 'ZRANGE';
    }

    protected function filterArguments(Array $arguments) {
        if (count($arguments) === 4) {
            $lastType = gettype($arguments[3]);
            if ($lastType === 'string' && strtolower($arguments[3]) === 'withscores') {
                // Used for compatibility with older versions
                $arguments[3] = array('WITHSCORES' => true);
                $lastType = 'array';
            }
            if ($lastType === 'array') {
                $options = $this->prepareOptions(array_pop($arguments));
                return array_merge($arguments, $options);
            }
        }
        return $arguments;
    }

    protected function prepareOptions($options) {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();
        if (isset($opts['WITHSCORES'])) {
            $finalizedOpts[] = 'WITHSCORES';
        }
        return $finalizedOpts;
    }

    protected function withScores() {
        $arguments = $this->getArguments();
        if (count($arguments) < 4) {
            return false;
        }
        return strtoupper($arguments[3]) === 'WITHSCORES';
    }

    public function parseResponse($data) {
        if ($this->withScores()) {
            if ($data instanceof \Iterator) {
                return new MultiBulkResponseTuple($data);
            }
            $result = array();
            for ($i = 0; $i < count($data); $i++) {
                $result[] = array($data[$i], $data[++$i]);
            }
            return $result;
        }
        return $data;
    }
}
