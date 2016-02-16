<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\PubSub;

use Predis\Client;
use Predis\Profile;
use Predis\PubSub\Consumer as PubSubConsumer;
use PredisTestCase;

/**
 * @group realm-pubsub
 */
class ConsumerTest extends PredisTestCase
{
    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The current profile does not support PUB/SUB related commands.
     */
    public function testPubSubConsumerRequirePubSubRelatedCommand()
    {
        $profile = $this->getMock('Predis\Profile\ProfileInterface');
        $profile->expects($this->any())
                ->method('supportsCommands')
                ->will($this->returnValue(false));

        $client = new Client(null, array('profile' => $profile));

        new PubSubConsumer($client);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage Cannot initialize a PUB/SUB consumer over aggregate connections.
     */
    public function testPubSubConsumerDoesNotWorkOnClusters()
    {
        $cluster = $this->getMock('Predis\Connection\Aggregate\ClusterInterface');
        $client = new Client($cluster);

        new PubSubConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithoutSubscriptionsDoesNotStartConsumer()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');

        $client = $this->getMock('Predis\Client', array('executeCommand'), array($connection));
        $client->expects($this->never())->method('executeCommand');

        new PubSubConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithSubscriptionsStartsConsumer()
    {
        $profile = Profile\Factory::get(REDIS_SERVER_VERSION);

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->exactly(2))->method('writeRequest');

        $client = $this->getMock('Predis\Client', array('createCommand', 'writeRequest'), array($connection));
        $client->expects($this->exactly(2))
               ->method('createCommand')
               ->with($this->logicalOr($this->equalTo('subscribe'), $this->equalTo('psubscribe')))
               ->will($this->returnCallback(function ($id, $args) use ($profile) {
                   return $profile->createCommand($id, $args);
               }));

        $options = array('subscribe' => 'channel:foo', 'psubscribe' => 'channels:*');

        new PubSubConsumer($client, $options);
    }

    /**
     * @group disconnected
     */
    public function testStoppingConsumerWithTrueClosesConnection()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');

        $client = $this->getMock('Predis\Client', array('disconnect'), array($connection));
        $client->expects($this->exactly(1))->method('disconnect');

        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $connection->expects($this->never())->method('writeRequest');

        $pubsub->stop(true);
    }

    /**
     * @group disconnected
     */
    public function testStoppingConsumerWithFalseSendsUnsubscriptions()
    {
        $profile = Profile\Factory::get(REDIS_SERVER_VERSION);
        $classUnsubscribe = $profile->getCommandClass('unsubscribe');
        $classPunsubscribe = $profile->getCommandClass('punsubscribe');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');

        $client = $this->getMock('Predis\Client', array('disconnect'), array($connection));

        $options = array('subscribe' => 'channel:foo', 'psubscribe' => 'channels:*');
        $pubsub = new PubSubConsumer($client, $options);

        $connection->expects($this->exactly(2))
                   ->method('writeRequest')
                   ->with($this->logicalOr(
                       $this->isInstanceOf($classUnsubscribe),
                       $this->isInstanceOf($classPunsubscribe)
                   ));

        $pubsub->stop(false);
    }

    /**
     * @group disconnected
     */
    public function testIsNotValidWhenNotSubscribed()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $client = $this->getMock('Predis\Client', array('disconnect'), array($connection));

        $pubsub = new PubSubConsumer($client);

        $this->assertFalse($pubsub->valid());
        $this->assertNull($pubsub->next());
    }

    /**
     * @group disconnected
     */
    public function testHandlesPongMessages()
    {
        $rawmessage = array('pong', '');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())->method('read')->will($this->returnValue($rawmessage));

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $message = $pubsub->current();
        $this->assertSame('pong', $message->kind);
        $this->assertSame('', $message->payload);
    }

    /**
     * @group disconnected
     */
    public function testHandlesPongMessagesWithPayload()
    {
        $rawmessage = array('pong', 'foobar');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())->method('read')->will($this->returnValue($rawmessage));

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $message = $pubsub->current();
        $this->assertSame('pong', $message->kind);
        $this->assertSame('foobar', $message->payload);
    }

    /**
     * @group disconnected
     */
    public function testReadsMessageFromConnection()
    {
        $rawmessage = array('message', 'channel:foo', 'message from channel');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())->method('read')->will($this->returnValue($rawmessage));

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $message = $pubsub->current();
        $this->assertSame('message', $message->kind);
        $this->assertSame('channel:foo', $message->channel);
        $this->assertSame('message from channel', $message->payload);
    }

    /**
     * @group disconnected
     */
    public function testReadsPmessageFromConnection()
    {
        $rawmessage = array('pmessage', 'channel:*', 'channel:foo', 'message from channel');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())->method('read')->will($this->returnValue($rawmessage));

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('psubscribe' => 'channel:*'));

        $message = $pubsub->current();
        $this->assertSame('pmessage', $message->kind);
        $this->assertSame('channel:*', $message->pattern);
        $this->assertSame('channel:foo', $message->channel);
        $this->assertSame('message from channel', $message->payload);
    }

    /**
     * @group disconnected
     */
    public function testReadsSubscriptionMessageFromConnection()
    {
        $rawmessage = array('subscribe', 'channel:foo', 1);

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())->method('read')->will($this->returnValue($rawmessage));

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $message = $pubsub->current();
        $this->assertSame('subscribe', $message->kind);
        $this->assertSame('channel:foo', $message->channel);
        $this->assertSame(1, $message->payload);
    }

    /**
     * @group disconnected
     */
    public function testReadsUnsubscriptionMessageFromConnection()
    {
        $rawmessage = array('unsubscribe', 'channel:foo', 1);

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())->method('read')->will($this->returnValue($rawmessage));

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $message = $pubsub->current();
        $this->assertSame('unsubscribe', $message->kind);
        $this->assertSame('channel:foo', $message->channel);
        $this->assertSame(1, $message->payload);
    }

    /**
     * @group disconnected
     */
    public function testUnsubscriptionMessageWithZeroChannelCountInvalidatesConsumer()
    {
        $rawmessage = array('unsubscribe', 'channel:foo', 0);

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())->method('read')->will($this->returnValue($rawmessage));

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $this->assertTrue($pubsub->valid());

        $message = $pubsub->current();
        $this->assertSame('unsubscribe', $message->kind);
        $this->assertSame('channel:foo', $message->channel);
        $this->assertSame(0, $message->payload);

        $this->assertFalse($pubsub->valid());
    }

    /**
     * @group disconnected
     */
    public function testGetUnderlyingClientInstance()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client);

        $this->assertSame($client, $pubsub->getClient());
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testPubSubAgainstRedisServer()
    {
        $parameters = array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'database' => REDIS_SERVER_DBNUM,
            // Prevents suite from handing on broken test
            'read_write_timeout' => 2,
        );

        $options = array('profile' => REDIS_SERVER_VERSION);
        $messages = array();

        $producer = new Client($parameters, $options);
        $producer->connect();

        $consumer = new Client($parameters, $options);
        $consumer->connect();

        $pubsub = new PubSubConsumer($consumer);
        $pubsub->subscribe('channel:foo');

        $producer->publish('channel:foo', 'message1');
        $producer->publish('channel:foo', 'message2');
        $producer->publish('channel:foo', 'QUIT');

        foreach ($pubsub as $message) {
            if ($message->kind !== 'message') {
                continue;
            }
            $messages[] = ($payload = $message->payload);
            if ($payload === 'QUIT') {
                $pubsub->stop();
            }
        }

        $this->assertSame(array('message1', 'message2', 'QUIT'), $messages);
        $this->assertFalse($pubsub->valid());
        $this->assertEquals('ECHO', $consumer->echo('ECHO'));
    }

    /**
     * @group connected
     * @requires extension pcntl
     */
    public function testPubSubAgainstRedisServerBlocking()
    {
        $parameters = array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'database' => REDIS_SERVER_DBNUM,
            'read_write_timeout' => -1, // -1 to set blocking reads
        );

        $options = array('profile' => REDIS_SERVER_VERSION);

        // create consumer before forking so the child can disconnect it
        $consumer = new Client($parameters, $options);
        $consumer->connect();

        /*
         * fork
         *  parent: consumer
         *  child: producer
         */
        if ($childPID = pcntl_fork()) {
            $messages = array();

            $pubsub = new PubSubConsumer($consumer);
            $pubsub->subscribe('channel:foo');

            foreach ($pubsub as $message) {
                if ($message->kind !== 'message') {
                    continue;
                }
                $messages[] = ($payload = $message->payload);
                if ($payload === 'QUIT') {
                    $pubsub->stop();
                }
            }

            $this->assertSame(array('message1', 'message2', 'QUIT'), $messages);
            $this->assertFalse($pubsub->valid());
            $this->assertEquals('ECHO', $consumer->echo('ECHO'));

            // kill the child
            posix_kill($childPID, SIGKILL);
        } else {
            // create producer, read_write_timeout = 2 because it doesn't do blocking reads anyway
            $producer = new Client(array_replace($parameters, array('read_write_timeout' => 2)), $options);
            $producer->connect();

            $producer->publish('channel:foo', 'message1');
            $producer->publish('channel:foo', 'message2');

            $producer->publish('channel:foo', 'QUIT');

            // sleep, giving the consumer a chance to respond to the QUIT message
            sleep(1);

            // disconnect the consumer because otherwise it could remain stuck in blocking read
            //  if it failed to respond to the QUIT message
            $consumer->disconnect();

            // exit child
            exit(0);
        }
    }
}
