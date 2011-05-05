<?php

namespace Predis\Commands;

class ZSetUnionStore extends Command {
    public function getId() {
        return 'ZUNIONSTORE';
    }

    protected function filterArguments(Array $arguments) {
        $options = array();
        $argc = count($arguments);
        if ($argc > 2 && is_array($arguments[$argc - 1])) {
            $options = $this->prepareOptions(array_pop($arguments));
        }
        if (is_array($arguments[1])) {
            $arguments = array_merge(
                array($arguments[0], count($arguments[1])),
                $arguments[1]
            );
        }
        return array_merge($arguments, $options);
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

    protected function onPrefixKeys(Array $arguments, $prefix) {
        $arguments[0] = "$prefix{$arguments[0]}";
        $length = ((int) $arguments[1]) + 2;
        for ($i = 2; $i < $length; $i++) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }
        return $arguments;
    }

    protected function canBeHashed() {
        $args = $this->getArguments();
        return $this->checkSameHashForKeys(
            array_merge(array($args[0]), array_slice($args, 2, $args[1]))
        );
    }
}
