<?php
/**
 * Apply patches to support newer PHP versions.
 */

$patches = array(
    'phpunit_mock_objects.patch' => 'phpunit/phpunit-mock-objects',
    'phpunit_php7.patch' => 'phpunit/phpunit',
    'phpunit_php8.patch' => 'phpunit/phpunit',
    'phpunit_php81.patch' => 'phpunit/phpunit',
);

foreach ($patches as $patch => $package) {
    chdir(__DIR__.'/../vendor/'.$package);
    passthru(sprintf('patch -p1 -f < ../../../tests/%s', $patch));
}
