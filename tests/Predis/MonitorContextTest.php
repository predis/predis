<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use \PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\Profiles\ServerProfile;

/**
 * @group realm-monitor
 */
class MonitorContextTest extends StandardTestCase
{
    /**
     * @group disconnected
     * @expectedException Predis\NotSupportedException
     * @expectedExceptionMessage The current profile does not support the MONITOR command
     */
    public function testMonitorContextRequireMonitorCommand()
    {
        $profile = $this->getMock('Predis\Profiles\IServerProfile');
        $profile->expects($this->once())
                ->method('supportsCommand')
                ->with('monitor')
                ->will($this->returnValue(false));

        $client = new Client(null, array('profile' => $profile));
        $monitor = new MonitorContext($client);
    }

    /**
     * @group disconnected
     * @expectedException Predis\NotSupportedException
     * @expectedExceptionMessage Cannot initialize a monitor context over a cluster of connections
     */
    public function testMonitorContextDoesNotWorkOnClusters()
    {
        $cluster = $this->getMock('Predis\Network\IConnectionCluster');

        $client = new Client($cluster);
        $monitor = new MonitorContext($client);
    }

    /**
     * @group disconnected
     */
    public function testConstructorOpensContext()
    {
        $cmdMonitor = ServerProfile::getDefault()->createCommand('monitor');

        $connection = $this->getMock('Predis\Network\IConnectionSingle');

        $client = $this->getMock('Predis\Client', array('createCommand', 'executeCommand'), array($connection));
        $client->expects($this->once())
               ->method('createCommand')
               ->with('monitor', array())
               ->will($this->returnValue($cmdMonitor));
        $client->expects($this->once())
               ->method('executeCommand')
               ->with($cmdMonitor);

        $monitor = new MonitorContext($client);
    }

    /**
     * @group disconnected
     * @todo We should investigate why disconnect is invoked 2 times in this test,
     *       but the reason is probably that the GC invokes __destruct() on monitor
     *       thus calling $client->disconnect() a second time at the end of the test.
     */
    public function testClosingContextClosesConnection()
    {
        $connection = $this->getMock('Predis\Network\IConnectionSingle');

        $client = $this->getMock('Predis\Client', array('disconnect'), array($connection));
        $client->expects($this->exactly(2))->method('disconnect');

        $monitor = new MonitorContext($client);
        $monitor->closeContext();
    }

    /**
     * @group disconnected
     */
    public function testGarbageCollectorRunClosesContext()
    {
        $connection = $this->getMock('Predis\Network\IConnectionSingle');

        $client = $this->getMock('Predis\Client', array('disconnect'), array($connection));
        $client->expects($this->once())->method('disconnect');

        $monitor = new MonitorContext($client);
        unset($monitor);
    }

    /**
     * @group disconnected
     */
    public function testCurrentReadsMessageFromConnection()
    {
        $message = '1323367530.939137 (db 15) "MONITOR"';

        $connection = $this->getMock('Predis\Network\IConnectionSingle');
        $connection->expects($this->once())
                   ->method('read')
                   ->will($this->returnValue($message));

        $client = new Client($connection);
        $monitor = new MonitorContext($client);

        $payload = $monitor->current();
        $this->assertSame(1323367530, (int) $payload->timestamp);
        $this->assertSame(15, $payload->database);
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

        $echoed = array();

        $producer = new Client($parameters, REDIS_SERVER_VERSION);
        $producer->connect();

        $consumer = new Client($parameters, REDIS_SERVER_VERSION);
        $consumer->connect();

        $monitor = new MonitorContext($consumer);

        $producer->echo('message1');
        $producer->echo('message2');
        $producer->echo('QUIT');

        foreach ($monitor as $message) {
            if ($message->command == 'ECHO') {
                $echoed[] = $arguments = trim($message->arguments, '"');
                if ($arguments == 'QUIT') {
                    $monitor->closeContext();
                }
            }
        }

        $this->assertSame(array('message1', 'message2', 'QUIT'), $echoed);
        $this->assertFalse($monitor->valid());
        $this->assertTrue($consumer->ping());
    }
}
