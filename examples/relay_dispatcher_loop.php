<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/shared.php';

$client = new Predis\Client(
    $single_server + ['read_write_timeout' => 0],
    ['connections' => 'relay']
);

// Relay dispatches messages through a blocking callback instead of an iterator.
$callbacks = [
    'notifications' => static function ($payload, $relay) {
        echo "Notification: {$payload}", PHP_EOL;
    },
    'control_channel' => static function ($payload, $relay) {
        if ($payload === 'quit_loop') {
            // Unsubscribe from all channels to return from the dispatch loop.
            $relay->unsubscribe();
        }
    },
];

$dispatch = static function ($message, $relay) use ($callbacks) {
    if ($message->kind === 'message' && isset($callbacks[$message->channel])) {
        $callbacks[$message->channel]($message->payload, $relay);
    }
};

$pubsub = $client->pubSubLoop();
$pubsub->subscribe(...array_merge(array_keys($callbacks), [$dispatch]));

// In another terminal:
//   redis-cli PUBLISH notifications "Make it so."
//   redis-cli PUBLISH control_channel quit_loop

echo 'Dispatcher stopped.', PHP_EOL;
