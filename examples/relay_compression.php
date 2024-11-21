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

require __DIR__ . '/shared.php';

// Enable igbinary serializer as well as LZ4 compression
$options = [
    'serializer' => 'igbinary',
    'compression' => 'lz4',
];

$client = new Predis\Client($single_server + $options, [
    'connections' => 'relay',
]);

$quote = (object) [
    'author' => 'Jean-Luc Picard',
    'text' => 'I look forward to your report Mr. Broccoli.',
];

// Serialize object and apply LZ4 compression, then write key to Redis
$client->set('quote', $client->pack($quote));

// NOTE: In Predis v3.x serialization and compression will happen
// automatically without the need to call `pack()` and `unpack()`

// Retrieve raw binary value from Redis
$raw = $client->get('quote');

// Decompress and unserialize binary value
$data = $client->unpack($raw);

var_dump($quote == $data); // true

var_dump($data);

/*
object(stdClass)#11 (2) {
    ["author"]=>string(15) "Jean-Luc Picard"
    ["text"]=>string(43) "I look forward to your report Mr. Broccoli."
}
*/
