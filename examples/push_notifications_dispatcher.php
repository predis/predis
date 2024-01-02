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

use Predis\ClientInterface;
use Predis\Consumer\DispatcherLoopInterface;
use Predis\Consumer\Push\DispatcherLoop;
use Predis\Consumer\Push\PushResponseInterface;

require __DIR__ . '/shared.php';

// 1. Create client with RESP3 protocol specified. Push notifications allowed only in RESP3 mode.
$client = new Predis\Client($single_server + ['read_write_timeout' => 0, 'protocol' => 3]);

// 2. Create push notifications consumer. Provides callback where current consumer subscribes to few channels before enter the loop.
$push = $client->push(static function (ClientInterface $client) {
    $response = $client->subscribe('channel', 'control');
    $status = ($response[2] === 1) ? 'OK' : 'FAILED';
    echo "Channel subscription status: {$status}\n";
});

// 3. Storage for upcoming notifications.
$messages = [];

// 4. Create dispatcher for push notifications.
$dispatcher = new DispatcherLoop($push);

// 5. Attach callback for message data type. Print every message and store them in storage.
// Send following commands via redis-cli to test:
//
// PUBLISH channel message1
// PUBLISH channel message2
// PUBLISH channel message3
// PUBLISH control terminate
// Data types should be changed in near future. Instead of Message data type it should be one of kind data types.

$dispatcher->attachCallback(
    PushResponseInterface::MESSAGE_DATA_TYPE,
    static function (array $payload, DispatcherLoopInterface $dispatcher) {
        global $messages;
        [$channel, $message] = $payload;

        if ($channel === 'control' && $message === 'terminate') {
            echo "Terminating notification consumer.\n";
            $dispatcher->stop();

            return;
        }

        $messages[] = $message;
        echo "Received message: {$message}\n";
    }
);

// 6. Run consumer loop with attached callbacks.
$dispatcher->run();

// 7. Count all messages that were received during consumer loop.
$messagesCount = count($messages);
echo "We received: {$messagesCount} messages\n";
