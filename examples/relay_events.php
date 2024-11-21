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

$key = null;

$client = new Predis\Client($single_server, [
    'connections' => 'relay',
]);

/** @var Predis\Connection\RelayConnection $relay */
$relay = $client->getConnection();

// establish connection
$client->ping();

// register `FLUSH*` callback
$relay->onFlushed(
    static function (Relay\Event $event) use (&$key) {
        echo 'Redis was flushed, unsetting $key...' . PHP_EOL;
        $key = null;
    }
);

// register `INVALIDATE` callback
$relay->onInvalidated(
    static function (Relay\Event $event) use (&$key) {
        if ($event->key === 'library') {
            echo "The `{$event->key}` key was invalidated, unsetting \$key..." . PHP_EOL;
            $key = null;
        }
    }
);

// Write key to Redis
$client->set('library', mt_rand());

// Retrieve key once from Redis, then cached in Relay and $key
$key = $client->get('library');

while (true) {
    echo '$key is: ' . var_export($key, true) . PHP_EOL;

    // To trigger our event callbacks, we need to either interact with Relay:
    $client->get(mt_rand());

    // ... or alternatively dispatch events directly on Relay:
    $relay->dispatchEvents();

    sleep(1);
}
