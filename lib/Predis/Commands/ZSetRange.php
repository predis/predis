<?php

namespace Predis\Commands;

use Predis\Iterators\MultiBulkResponseTuple;

class ZSetRange extends Command {
    private $_withScores = false;
    public function getId() { return 'ZRANGE'; }
    public function filterArguments(Array $arguments) {
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
            $this->_withScores = true;
        }
        return $finalizedOpts;
    }
    public function parseResponse($data) {
        if ($this->_withScores) {
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
