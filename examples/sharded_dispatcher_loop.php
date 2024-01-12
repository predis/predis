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

require __DIR__ . '/shared.php';

// This is a basic example on how to use the Predis\DispatcherLoop class in sharded pub/sub mode.
//
// Both channels belong to different shards. So in sharded mode we can subscribe
// and receive messages from different shard channels.
//
// To see this example in action you can just use redis-cli and publish some
// messages to the '{channels}_events' and 'control' channel, e.g.:

// ./redis-cli
// SPUBLISH {channels}_events first
// SPUBLISH {channels}_events second
// SPUBLISH {channels}_events third
// SPUBLISH control terminate_dispatcher

// 1. Create client and setup RW timeout to 0.
$client = new Client(
    [
        'tcp://127.0.0.1:6372?read_write_timeout=0',
        'tcp://127.0.0.1:6373?read_write_timeout=0',
        'tcp://127.0.0.1:6374?read_write_timeout=0',
    ], [
    'cluster' => 'redis',
]);

// 2. Run pub/sub loop.
$pubSub = $client->pubSubLoop();

// 3. Create a dispatcher loop instance and attach a bunch of callbacks.
$dispatcher = new Predis\Consumer\PubSub\DispatcherLoop($pubSub);

// 4. Demonstrate how to use a callable class as a callback for the dispatcher loop.
class EventsListener implements Countable
{
    private $events;

    public function __construct()
    {
        $this->events = [];
    }

    public function count()
    {
        return count($this->events);
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function __invoke($payload, $dispatcher)
    {
        $this->events[] = $payload;
    }
}

// 5. Attach our callable class to the dispatcher.
$dispatcher->attachCallback('{channels}_events', $events = new EventsListener());

// 6. Attach a function to control the dispatcher loop termination with a message.
$dispatcher->attachCallback('control', function ($payload, $dispatcher) {
    if ($payload === 'terminate_dispatcher') {
        $dispatcher->stop();
    }
});

// 7. Run the dispatcher loop until the callback attached to the 'control' channel
// receives 'terminate_dispatcher' as a message.
$dispatcher->run();

// Display our achievements!
echo "We received {$events->count()} messages!", PHP_EOL;

// Say goodbye :-)
echo 'Goodbye from Redis!', PHP_EOL;
