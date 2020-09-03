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

use PredisTestCase;
use Predis\Client;
use Predis\PubSub\Consumer as PubSubConsumer;

/**
 * @group realm-pubsub
 */
class ConsumerTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testPubSubConsumerRequirePubSubRelatedCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('PUB/SUB commands are not supported by the current command factory.');

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->any())
            ->method('supports')
            ->willReturn(false);

        $client = new Client(null, array('commands' => $commands));

        new PubSubConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testPubSubConsumerDoesNotWorkOnClusters(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('Cannot initialize a PUB/SUB consumer over aggregate connections');

        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();
        $client = new Client($cluster);

        new PubSubConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithoutSubscriptionsDoesNotStartConsumer(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        /** @var Client */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('executeCommand'))
            ->setConstructorArgs(array($connection))
            ->getMock();

        $client->expects($this->never())
            ->method('executeCommand');

        new PubSubConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithSubscriptionsStartsConsumer(): void
    {
        $commands = $this->getCommandFactory();

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->exactly(2))->method('writeRequest');

        /** @var Client */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('createCommand'))
            ->addMethods(array('writeRequest'))
            ->setConstructorArgs(array($connection))
            ->getMock();
        $client
            ->expects($this->exactly(2))
            ->method('createCommand')
            ->with($this->logicalOr($this->equalTo('subscribe'), $this->equalTo('psubscribe')))
            ->willReturnCallback(function ($id, $args) use ($commands) {
                return $commands->create($id, $args);
            });

        $options = array('subscribe' => 'channel:foo', 'psubscribe' => 'channels:*');

        new PubSubConsumer($client, $options);
    }

    /**
     * @group disconnected
     */
    public function testStoppingConsumerWithTrueClosesConnection(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        /** @var Client */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('disconnect'))
            ->setConstructorArgs(array($connection))
            ->getMock();
        $client
            ->expects($this->once())
            ->method('disconnect');

        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $connection->expects($this->never())->method('writeRequest');

        $pubsub->stop(true);
    }

    /**
     * @group disconnected
     */
    public function testStoppingConsumerWithFalseSendsUnsubscriptions(): void
    {
        $commands = $this->getCommandFactory();
        $classUnsubscribe = $commands->getCommandClass('unsubscribe');
        $classPunsubscribe = $commands->getCommandClass('punsubscribe');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        /** @var Client */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('disconnect'))
            ->setConstructorArgs(array($connection))
            ->getMock();

        $options = array('subscribe' => 'channel:foo', 'psubscribe' => 'channels:*');
        $pubsub = new PubSubConsumer($client, $options);

        $connection
            ->expects($this->exactly(2))
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
    public function testIsNotValidWhenNotSubscribed(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        /** @var Client */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('disconnect'))
            ->setConstructorArgs(array($connection))
            ->getMock();

        $pubsub = new PubSubConsumer($client);

        $this->assertFalse($pubsub->valid());
        $this->assertNull($pubsub->next());
    }

    /**
     * @group disconnected
     */
    public function testHandlesPongMessages(): void
    {
        $rawmessage = array('pong', '');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($rawmessage);

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $message = $pubsub->current();
        $this->assertSame('pong', $message->kind);
        $this->assertSame('', $message->payload);
    }

    /**
     * @group disconnected
     */
    public function testHandlesPongMessagesWithPayload(): void
    {
        $rawmessage = array('pong', 'foobar');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($rawmessage);

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client, array('subscribe' => 'channel:foo'));

        $message = $pubsub->current();
        $this->assertSame('pong', $message->kind);
        $this->assertSame('foobar', $message->payload);
    }

    /**
     * @group disconnected
     */
    public function testReadsMessageFromConnection(): void
    {
        $rawmessage = array('message', 'channel:foo', 'message from channel');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($rawmessage);

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
    public function testReadsPmessageFromConnection(): void
    {
        $rawmessage = array('pmessage', 'channel:*', 'channel:foo', 'message from channel');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($rawmessage);

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
    public function testReadsSubscriptionMessageFromConnection(): void
    {
        $rawmessage = array('subscribe', 'channel:foo', 1);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($rawmessage);

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
    public function testReadsUnsubscriptionMessageFromConnection(): void
    {
        $rawmessage = array('unsubscribe', 'channel:foo', 1);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($rawmessage);

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
    public function testUnsubscriptionMessageWithZeroChannelCountInvalidatesConsumer(): void
    {
        $rawmessage = array('unsubscribe', 'channel:foo', 0);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($rawmessage);

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
    public function testGetUnderlyingClientInstance(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $client = new Client($connection);
        $pubsub = new PubSubConsumer($client);

        $this->assertSame($client, $pubsub->getClient());
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    // NOTE: the following 2 tests fail at random without any apparent reason
    // when executed on our CI environments and these failures are not tied
    // to a particular version of PHP or Redis. It is most likely some weird
    // timing issue on busy systems as it is really rare to get it triggered
    // locally. The chances it is a bug in the library are pretty low so for
    // now we just mark this test skipped on our CI environments (but still
    // enabled for local test runs) and "debug" this issue using a separate
    // branch to avoid having spurious failures on main development branches
    // which is utterly annoying.

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testPubSubAgainstRedisServer(): void
    {
        $this->markTestSkippedOnCIEnvironment(
            'Test temporarily skipped on CI environments, see note in the body of the test' // TODO
        );

        $parameters = array(
            'host' => constant('REDIS_SERVER_HOST'),
            'port' => constant('REDIS_SERVER_PORT'),
            'database' => constant('REDIS_SERVER_DBNUM'),
            // Prevents suite from handing on broken test
            'read_write_timeout' => 2,
        );

        $messages = array();

        $producer = new Client($parameters);
        $producer->connect();

        $consumer = new Client($parameters);
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
     * @requiresRedisVersion >= 2.0.0
     * @requires extension pcntl
     */
    public function testPubSubAgainstRedisServerBlocking(): void
    {
        $this->markTestSkippedOnCIEnvironment(
            'Test temporarily skipped on CI environments, see note in the body of the test' // TODO
        );

        $parameters = array(
            'host' => constant('REDIS_SERVER_HOST'),
            'port' => constant('REDIS_SERVER_PORT'),
            'database' => constant('REDIS_SERVER_DBNUM'),
            'read_write_timeout' => -1, // -1 to set blocking reads
        );

        // create consumer before forking so the child can disconnect it
        $consumer = new Client($parameters);
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
            $producer = new Client(array_replace($parameters, array('read_write_timeout' => 2)));
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
