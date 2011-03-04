<?php

namespace Predis\Commands;

class ZSetUnionStore extends Command {
    public function getId() { return 'ZUNIONSTORE'; }
    public function filterArguments(Array $arguments) {
        $options = array();
        $argc    = count($arguments);
        if ($argc > 1 && is_array($arguments[$argc - 1])) {
            $options = $this->prepareOptions(array_pop($arguments));
        }
        $args = is_array($arguments[0]) ? $arguments[0] : $arguments;
        return  array_merge($args, $options);
    }
    private function prepareOptions($options) {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();
        if (isset($opts['WEIGHTS']) && is_array($opts['WEIGHTS'])) {
            $finalizedOpts[] = 'WEIGHTS';
            foreach ($opts['WEIGHTS'] as $weight) {
                $finalizedOpts[] = $weight;
            }
        }
        if (isset($opts['AGGREGATE'])) {
            $finalizedOpts[] = 'AGGREGATE';
            $finalizedOpts[] = $opts['AGGREGATE'];
        }
        return $finalizedOpts;
    }
}
