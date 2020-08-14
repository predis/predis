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

use Predis\Client;
use Predis\Monitor\Consumer as MonitorConsumer;
use Predis\Profile;
use PredisTestCase;

/**
 * @group realm-monitor
 */
class ConsumerTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testMonitorConsumerRequireMonitorCommand()
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("The current profile does not support 'MONITOR'");

        $profile = $this->getMockBuilder('Predis\Profile\ProfileInterface')->getMock();
        $profile->expects($this->once())
                ->method('supportsCommand')
                ->with('MONITOR')
                ->will($this->returnValue(false));

        $client = new Client(null, array('profile' => $profile));

        new MonitorConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testMonitorConsumerDoesNotWorkOnClusters()
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('Cannot initialize a monitor consumer over aggregate connections');

        $cluster = $this->getMockBuilder('Predis\Connection\Aggregate\ClusterInterface')->getMock();
        $client = new Client($cluster);

        new MonitorConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testConstructorStartsConsumer()
    {
        $cmdMonitor = Profile\Factory::getDefault()->createCommand('monitor');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(array('createCommand', 'executeCommand'))
            ->setConstructorArgs(array($connection))
            ->getMock();
        $client->expects($this->once())
               ->method('createCommand')
               ->with('MONITOR', array())
               ->will($this->returnValue($cmdMonitor));
        $client->expects($this->once())
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
    public function testStoppingConsumerClosesConnection()
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(array('disconnect'))
            ->setConstructorArgs(array($connection))
            ->getMock();
        $client->expects($this->exactly(2))->method('disconnect');

        $monitor = new MonitorConsumer($client);
        $monitor->stop();
    }

    /**
     * @group disconnected
     */
    public function testGarbageCollectorRunStopsConsumer()
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(array('disconnect'))
            ->setConstructorArgs(array($connection))
            ->getMock();
        $client->expects($this->once())->method('disconnect');

        $monitor = new MonitorConsumer($client);
        unset($monitor);
    }

    /**
     * @group disconnected
     */
    public function testReadsMessageFromConnectionToRedis24()
    {
        $message = '1323367530.939137 (db 15) "MONITOR"';

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
                   ->method('read')
                   ->will($this->returnValue($message));

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
    public function testReadsMessageFromConnectionToRedis26()
    {
        $message = '1323367530.939137 [15 127.0.0.1:37265] "MONITOR"';

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
                   ->method('read')
                   ->will($this->returnValue($message));

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
    public function testMonitorAgainstRedisServer()
    {
        $parameters = array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'database' => REDIS_SERVER_DBNUM,
            // Prevents suite from handing on broken test
            'read_write_timeout' => 2,
        );

        $options = array('profile' => REDIS_SERVER_VERSION);
        $echoed = array();

        $producer = new Client($parameters, $options);
        $producer->connect();

        $consumer = new Client($parameters, $options);
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
