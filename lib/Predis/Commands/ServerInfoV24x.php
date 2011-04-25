<?php

namespace Predis\Commands;

class ServerInfoV24x extends ServerInfo {
    public function parseResponse($data) {
        $info      = array();
        $current   = null;
        $infoLines = explode("\r\n", $data, -1);
        foreach ($infoLines as $row) {
            if ($row === '') {
                continue;
            }
            if (preg_match('/^# (\w+)$/', $row, $matches)) {
                $info[$matches[1]] = array();
                $current = &$info[$matches[1]];
                continue;
            }
            list($k, $v) = explode(':', $row);
            if (!preg_match('/^db\d+$/', $k)) {
                if ($k === 'allocation_stats') {
                    $current[$k] = $this->parseAllocationStats($v);
                    continue;
                }
                $current[$k] = $v;
            }
            else {
                $current[$k] = $this->parseDatabaseStats($v);
            }
        }
        return $info;
    }
}
