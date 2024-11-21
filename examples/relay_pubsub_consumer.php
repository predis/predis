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

// Create a Relay client and disable r/w timeout on the connection
$client = new Predis\Client(
    $single_server + ['read_write_timeout' => 0],
    ['connections' => 'relay']
);

// Initialize a new pubsub consumer.
$pubsub = $client->pubSubLoop();

// When using Relay you cannot use foreach-loops to iterate
// over messages instead use a callback function
$poorMansKafka = function ($message, $client) {
    switch ($message->kind) {
        case 'subscribe':
            echo "Subscribed to {$message->channel}", PHP_EOL;
            break;

        case 'message':
        case 'pmessage':
            if ($message->channel == 'control_channel') {
                if ($message->payload == 'quit_loop') {
                    echo 'Aborting pubsub loop...', PHP_EOL;
                    $client->unsubscribe();
                } else {
                    echo "Received an unrecognized command: {$message->payload}.", PHP_EOL;
                }
            } else {
                echo "Received the message from `{$message->channel}` channel:",
                PHP_EOL, "  {$message->payload}", PHP_EOL, PHP_EOL;
            }
    }
};

// Subscribe to your channels and start processing the messages.
$pubsub->subscribe('control_channel', 'notifications', $poorMansKafka);

// Open a terminal and use redis-cli to push messages to the channels. Examples:
//   redis-cli PUBLISH notifications "this is a test"
//   redis-cli PUBLISH control_channel quit_loop

// When using Relay, there is no need to unset the pubsub consumer instance when you are done

// Say goodbye :-)
$version = redis_version($client->info());
echo "Goodbye from Redis $version!", PHP_EOL;
