<?php

if (!defined(__DIR__)) {
    define('__DIR__', dirname(__FILE__));
}

require_once(__DIR__ . '/../lib/Predis.php');
require_once(__DIR__ . '/../lib/Predis_Compatibility.php');
require_once(__DIR__ . '/../test/PredisShared.php');
