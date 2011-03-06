<?php

namespace Predis\Commands;

class Info extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'INFO'; }
    public function parseResponse($data) {
        $info      = array();
        $infoLines = explode("\r\n", $data, -1);
        foreach ($infoLines as $row) {
            @list($k, $v) = explode(':', $row);
            if ($row === '' || !isset($v)) {
                continue;
            }
            if (!preg_match('/^db\d+$/', $k)) {
                if ($k === 'allocation_stats') {
                    $info[$k] = $this->parseAllocationStats($v);
                    continue;
                }
                $info[$k] = $v;
            }
            else {
                $info[$k] = $this->parseDatabaseStats($v);
            }
        }
        return $info;
    }
    protected function parseDatabaseStats($str) {
        $db = array();
        foreach (explode(',', $str) as $dbvar) {
            list($dbvk, $dbvv) = explode('=', $dbvar);
            $db[trim($dbvk)] = $dbvv;
        }
        return $db;
    }
    protected function parseAllocationStats($str) {
        $stats = array();
        foreach (explode(',', $str) as $kv) {
            @list($size, $objects, $extra) = explode('=', $kv);
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
