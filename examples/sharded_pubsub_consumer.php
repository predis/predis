<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Predis\Client;
use Predis\PubSub\SubscriptionContext;

require __DIR__ . '/shared.php';

// 1. Create client and setup RW timeout to 0.
$client = new Client(
    [
        'tcp://127.0.0.1:6372?read_write_timeout=0',
        'tcp://127.0.0.1:6373?read_write_timeout=0',
        'tcp://127.0.0.1:6374?read_write_timeout=0',
    ], [
    'cluster' => 'redis',
]);

// 2. Run pub/sub loop. Sharded channels belongs to different shards.
$pubSub = $client->pubSubLoop();
$pubSub->ssubscribe('{channels}_notifications');
$pubSub->ssubscribe('control_channel');

// Start processing the pubsup messages. Open a terminal and use redis-cli
// to push messages to the channels. Examples:
//   ./redis-cli SPUBLISH {channels}_notifications "this is a test"
//   ./redis-cli SPUBLISH control_channel quit_loop
foreach ($pubSub as $message) {
    switch ($message->kind) {
        case 'ssubscribe':
            echo "Subscribed to {$message->channel}", PHP_EOL;
            break;

        case 'message':
            if ($message->channel == 'control_channel') {
                if ($message->payload == 'quit_loop') {
                    echo 'Aborting pubsub loop...', PHP_EOL;
                    $pubSub->sunsubscribe();
                } else {
                    echo "Received an unrecognized command: {$message->payload}.", PHP_EOL;
                }
            } else {
                echo "Received the following message from {$message->channel}:",
                PHP_EOL, "  {$message->payload}", PHP_EOL, PHP_EOL;
            }
            break;
    }
}

// Always unset the pubsub consumer instance when you are done! The
// class destructor will take care of cleanups and prevent protocol
// desynchronizations between the client and the server.
unset($pubsub);

// Say goodbye :-)
echo 'Goodbye from Redis!', PHP_EOL;
