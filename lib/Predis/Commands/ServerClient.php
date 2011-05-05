<?php

namespace Predis\Commands;

class ServerClient extends Command {
    public function getId() {
        return 'CLIENT';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        $args = array_change_key_case($this->getArguments(), CASE_UPPER);
        switch (strtoupper($args[0])) {
            case 'LIST':
                return $this->parseClientList($data);
            case 'KILL':
            default:
                return $data;
        }
    }

    protected function parseClientList($data) {
        $clients = array();
        foreach (explode("\n", $data, -1) as $clientData) {
            $client = array();
            foreach (explode(' ', $clientData) as $kv) {
                @list($k, $v) = explode('=', $kv);
                $client[$k] = $v;
            }
            $clients[] = $client;
        }
        return $clients;
    }
}
