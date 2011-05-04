<?php

namespace Predis\Commands;

class PrefixHelpers {
    public static function multipleKeys(Array $arguments, $prefix) {
        foreach ($arguments as &$key) {
            $key = "$prefix$key";
        }
        return $arguments;
    }

    public static function skipLastArgument(Array $arguments, $prefix) {
        $length = count($arguments);
        for ($i = 0; $i < $length - 1; $i++) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }
        return $arguments;
    }
}
