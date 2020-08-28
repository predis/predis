<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Monitor;

use PredisTestCase;
use Predis\Client;
use Predis\Monitor\Consumer as MonitorConsumer;

/**
 * @group realm-monitor
 */
class ConsumerTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testMonitorConsumerRequireMonitorCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("'MONITOR' is not supported by the current command factory.");

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->once())
            ->method('supports')
            ->with('MONITOR')
            ->willReturn(false);

        $client = new Client(null, array('commands' => $commands));

        new MonitorConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testMonitorConsumerDoesNotWorkOnClusters(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('Cannot initialize a monitor consumer over aggregate connections');

        $cluster = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $client = new Client($cluster);

        new MonitorConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testConstructorStartsConsumer(): void
    {
        $cmdMonitor = $this->getCommandFactory()->create('monitor');
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('createCommand', 'executeCommand'))
            ->setConstructorArgs(array($connection))
            ->getMock();
        $client
            ->expects($this->once())
            ->method('createCommand')
            ->with('MONITOR', array())
            ->willReturn($cmdMonitor);
        $client
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdMonitor);

        new MonitorConsumer($client);
    }

    /**
     * @group disconnected
     *
     * @todo Investigate why disconnect() is invoked 2 times in this test, but
     *       the reason is probably that the GC invokes __destruct() on monitor
     *       thus calling disconnect() a second time at the end of the test.
     */
    public function testStoppingConsumerClosesConnection(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('disconnect'))
            ->setConstructorArgs(array($connection))
            ->getMock();
        $client
            ->expects($this->exactly(2))
            ->method('disconnect');

        $monitor = new MonitorConsumer($client);

        $monitor->stop();
    }

    /**
     * @group disconnected
     */
    public function testGarbageCollectorRunStopsConsumer(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(array('disconnect'))
            ->setConstructorArgs(array($connection))
            ->getMock();
        $client
            ->expects($this->once())
            ->method('disconnect');

        $monitor = new MonitorConsumer($client);

        unset($monitor);
    }

    /**
     * @group disconnected
     */
    public function testReadsMessageFromConnectionToRedis24(): void
    {
        $message = '1323367530.939137 (db 15) "MONITOR"';

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($message);

        $client = new Client($connection);

        $monitor = new MonitorConsumer($client);
        $payload = $monitor->current();

        $this->assertSame(1323367530, (int) $payload->timestamp);
        $this->assertSame(15, $payload->database);
        $this->assertNull($payload->client);
        $this->assertSame('MONITOR', $payload->command);
        $this->assertNull($payload->arguments);
    }

    /**
     * @group disconnected
     */
    public function testReadsMessageFromConnectionToRedis26(): void
    {
        $message = '1323367530.939137 [15 127.0.0.1:37265] "MONITOR"';

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($message);

        $client = new Client($connection);

        $monitor = new MonitorConsumer($client);
        $payload = $monitor->current();

        $this->assertSame(1323367530, (int) $payload->timestamp);
        $this->assertSame(15, $payload->database);
        $this->assertSame('127.0.0.1:37265', $payload->client);
        $this->assertSame('MONITOR', $payload->command);
        $this->assertNull($payload->arguments);
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testMonitorAgainstRedisServer(): void
    {
        $parameters = array(
            'host' => constant('REDIS_SERVER_HOST'),
            'port' => constant('REDIS_SERVER_PORT'),
            'database' => constant('REDIS_SERVER_DBNUM'),
            // Prevents suite from handing on broken test
            'read_write_timeout' => 2,
        );

        $echoed = array();

        $producer = new Client($parameters);
        $producer->connect();

        $consumer = new Client($parameters);
        $consumer->connect();

        $monitor = new MonitorConsumer($consumer);

        $producer->echo('message1');
        $producer->echo('message2');
        $producer->echo('QUIT');

        foreach ($monitor as $message) {
            if ($message->command == 'ECHO') {
                $echoed[] = $arguments = trim($message->arguments, '"');
                if ($arguments == 'QUIT') {
                    $monitor->stop();
                }
            }
        }

        $this->assertSame(array('message1', 'message2', 'QUIT'), $echoed);
        $this->assertFalse($monitor->valid());
        $this->assertEquals('PONG', $consumer->ping());
    }
}
