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

// 3. Run consumer that will handle message data type push notifications. And stops if certain message will be sent to control channel.
// Send following commands via redis-cli to test:
//
// PUBLISH channel message1
// PUBLISH channel message2
// PUBLISH channel message3
// PUBLISH control terminate
// Data types should be changed in near future. Instead of Message data type it should be one of kind data types.

foreach ($push as $notification) {
    if ((null !== $notification) && $notification->getDataType() === PushResponseInterface::MESSAGE_DATA_TYPE) {
        if ($notification[1] === 'control' && $notification[2] === 'terminate') {
            echo "Terminating notification consumer.\n";
            $push->stop();
            break;
        }

        $message = $notification[2];

        echo "Received message: {$message}\n";
    }
}
