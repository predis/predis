<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (file_exists(__DIR__ . '/../autoload.php')) {
    require __DIR__ . '/../autoload.php';
} elseif (@include ('Predis/Autoloader.php')) {
    Predis\Autoloader::register();
} else {
    exit('ERROR: Unable to find a suitable mean to register Predis\Autoloader.');
}

require __DIR__ . '/PHPUnit/ArrayHasSameValuesConstraint.php';
require __DIR__ . '/PHPUnit/OneOfConstraint.php';
require __DIR__ . '/PHPUnit/AssertSameWithPrecisionConstraint.php';
require __DIR__ . '/PHPUnit/RedisCommandConstraint.php';
require __DIR__ . '/PHPUnit/PredisTestCase.php';
require __DIR__ . '/PHPUnit/PredisCommandTestCase.php';
require __DIR__ . '/PHPUnit/PredisConnectionTestCase.php';
require __DIR__ . '/PHPUnit/PredisDistributorTestCase.php';
