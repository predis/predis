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

$client = new Predis\Client($single_server + ['read_write_timeout' => 0, 'protocol' => 3]);
$push = $client->push(static function (ClientInterface $client) {
    $response = $client->client('TRACKING', 'ON', 'BCAST');
    echo "Key tracking status: {$response}\n";

    $response = $client->subscribe('channel', 'control');
    $status = ($response[2] === 1) ? 'OK' : 'FAILED';
    echo "Channel subscription status: {$status}\n";
});

foreach ($push as $notification) {
    if (null !== $notification) {
        if ($notification->getDataType() === PushResponseInterface::INVALIDATE_DATA_TYPE) {
            $dataType = $notification->getDataType();
            $invalidatedKey = $notification[1][0];

            echo "{$invalidatedKey} was invalidated\n";
        } elseif ($notification->getDataType() === PushResponseInterface::MESSAGE_DATA_TYPE) {
            if ($notification[1] === 'control' && $notification[2] === 'terminate') {
                echo "Terminating notification consumer.\n";
                $push->stop();
                break;
            }

            $message = $notification[2];

            echo "Received message: {$message}\n";
        }
    }
}
