<?php

use Predis\Cluster\CompactSlotMap;
use Predis\Cluster\SlotMap;

require 'autoload.php';

function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

$m1 = memory_get_usage();

$slots = [
    [0, 4000, '127.0.0.1:6379'],
    [4001, 8000, '127.0.0.1:6380'],
    [8001, 12000, '127.0.0.1:6381'],
    [12001, 16335, '127.0.0.1:6382'],

    [5000, 9000, 'c1'],
    [2400, 7300, 'c2'],
];

// $slotMap = new SlotMap();
$slotMap = new CompactSlotMap();
foreach ($slots as $slot) {
    $slotMap->setSlots($slot[0], $slot[1], $slot[2]);
}

$slotMap->printSlotRanges();

$m2 = memory_get_usage();

printf("m1: %s\n", convert($m1));
printf("m2: %s\n", convert($m2));
printf("m2-m1: %s\n", convert($m2 - $m1));


