<?php

namespace Predis\Commands;

class KeySort extends Command {
    public function getId() {
        return 'SORT';
    }

    protected function filterArguments(Array $arguments) {
        if (count($arguments) === 1) {
            return $arguments;
        }

        $query = array($arguments[0]);
        $sortParams = array_change_key_case($arguments[1], CASE_UPPER);

        if (isset($sortParams['BY'])) {
            $query[] = 'BY';
            $query[] = $sortParams['BY'];
        }
        if (isset($sortParams['GET'])) {
            $getargs = $sortParams['GET'];
            if (is_array($getargs)) {
                foreach ($getargs as $getarg) {
                    $query[] = 'GET';
                    $query[] = $getarg;
                }
            }
            else {
                $query[] = 'GET';
                $query[] = $getargs;
            }
        }
        if (isset($sortParams['LIMIT']) && is_array($sortParams['LIMIT'])
            && count($sortParams['LIMIT']) == 2) {

            $query[] = 'LIMIT';
            $query[] = $sortParams['LIMIT'][0];
            $query[] = $sortParams['LIMIT'][1];
        }
        if (isset($sortParams['SORT'])) {
            $query[] = strtoupper($sortParams['SORT']);
        }
        if (isset($sortParams['ALPHA']) && $sortParams['ALPHA'] == true) {
            $query[] = 'ALPHA';
        }
        if (isset($sortParams['STORE'])) {
            $query[] = 'STORE';
            $query[] = $sortParams['STORE'];
        }

        return $query;
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        $arguments[0] = "$prefix{$arguments[0]}";
        if (($count = count($arguments)) > 1) {
            for ($i = 1; $i < $count; $i++) {
                switch ($arguments[$i]) {
                    case 'BY':
                    case 'STORE':
                        $arguments[$i] = "$prefix{$arguments[++$i]}";
                        break;
                    case 'GET':
                        $value = $arguments[++$i];
                        if ($value !== '#') {
                            $arguments[$i] = "$prefix$value";
                        }
                        break;
                    case 'LIMIT';
                        $i += 2;
                        break;
                }
            }
        }
        return $arguments;
    }
}
