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

require __DIR__ . '/shared.php';

// This is a basic example on how to use the Predis\DispatcherLoop class.
//
// To see this example in action you can just use redis-cli and publish some
// messages to the 'events' and 'control' channel, e.g.:

// ./redis-cli
// PUBLISH events first
// PUBLISH events second
// PUBLISH events third
// PUBLISH control terminate_dispatcher

// Create a client and disable r/w timeout on the socket
$client = new Predis\Client($single_server + ['read_write_timeout' => 0]);

// Return an initialized PubSub consumer instance from the client.
$pubsub = $client->pubSubLoop();

// Create a dispatcher loop instance and attach a bunch of callbacks.
$dispatcher = new Predis\PubSub\DispatcherLoop($pubsub);

// Demonstrate how to use a callable class as a callback for the dispatcher loop.
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

// Attach our callable class to the dispatcher.
$dispatcher->attachCallback('events', $events = new EventsListener());

// Attach a function to control the dispatcher loop termination with a message.
$dispatcher->attachCallback('control', function ($payload, $dispatcher) {
    if ($payload === 'terminate_dispatcher') {
        $dispatcher->stop();
    }
});

// Run the dispatcher loop until the callback attached to the 'control' channel
// receives 'terminate_dispatcher' as a message.
$dispatcher->run();

// Display our achievements!
echo "We received {$events->count()} messages!", PHP_EOL;

// Say goodbye :-)
$version = redis_version($client->info());
echo "Goodbye from Redis $version!", PHP_EOL;
