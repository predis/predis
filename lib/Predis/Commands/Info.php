<?php

namespace Predis\Commands;

class Info extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'INFO'; }
    public function parseResponse($data) {
        $info      = array();
        $infoLines = explode("\r\n", $data, -1);
        foreach ($infoLines as $row) {
            list($k, $v) = explode(':', $row);
            if (!preg_match('/^db\d+$/', $k)) {
                if ($k === 'allocation_stats') {
                    $info[$k] = $this->parseAllocationStats($v);
                    continue;
                }
                $info[$k] = $v;
            }
            else {
                $db = array();
                foreach (explode(',', $v) as $dbvar) {
                    list($dbvk, $dbvv) = explode('=', $dbvar);
                    $db[trim($dbvk)] = $dbvv;
                }
                $info[$k] = $db;
            }
        }
        return $info;
    }
    protected function parseAllocationStats($str) {
        $stats = array();
        foreach (explode(',', $str) as $kv) {
            list($size, $objects, $extra) = explode('=', $kv);
            // hack to prevent incorrect values when parsing the >=256 key
            if (isset($extra)) {
                $size = ">=$objects";
                $objects = $extra;
            }
            $stats[$size] = $objects;
        }
        return $stats;
    }
}
