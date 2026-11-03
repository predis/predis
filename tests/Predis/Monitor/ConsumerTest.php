<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Monitor;

use Predis\Client;
use Predis\Connection\Parameters;
use Predis\Monitor\Consumer as MonitorConsumer;
use PredisTestCase;

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

        $client = new Client(null, ['commands' => $commands]);

        new MonitorConsumer($client);
    }

    /**
     * @group disconnected
     */
    public function testMonitorConsumerDoesNotWorkOnClusters(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('Cannot initialize a monitor consumer over cluster connections');

        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();
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
            ->onlyMethods(['createCommand', 'executeCommand'])
            ->setConstructorArgs([$connection])
            ->getMock();
        $client
            ->expects($this->once())
            ->method('createCommand')
            ->with('MONITOR', [])
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

        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['disconnect'])
            ->setConstructorArgs([$connection])
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

        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        /** @var \Predis\ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods(['disconnect'])
            ->setConstructorArgs([$connection])
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

        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

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

        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $client = new Client($connection);

        $monitor = new MonitorConsumer($client);
        $payload = $monitor->current();

        $this->assertSame(1323367530, (int) $payload->timestamp);
        $this->assertSame(15, $payload->database);
        $this->assertSame('127.0.0.1:37265', $payload->client);
        $this->assertSame('MONITOR', $payload->command);
        $this->assertNull($payload->arguments);
    }

    /**
     * @group disconnected
     * @dataProvider adversarialMonitorMessages
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotScanArgumentsForMetadata(string $metadata, int $database, ?string $address): void
    {
        $this->usePcreInterpreterWithLowBacktrackLimit();

        $arguments = '"' . str_repeat('[1 ', 22000) . '"';
        $message = '1323367530.939137 ' . $metadata . '"ECHO" ' . $arguments;

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($message);
        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $monitor = (new Client($connection))->monitor();
        $payload = $monitor->current();
        $error = preg_last_error();

        $this->assertSame(PREG_NO_ERROR, $error);
        $this->assertSame(1323367530.939137, $payload->timestamp);
        $this->assertSame($database, $payload->database);
        $this->assertSame($address, $payload->client);
        $this->assertSame('ECHO', $payload->command);
        $this->assertSame($arguments, $payload->arguments);
    }

    public function adversarialMonitorMessages(): array
    {
        return [
            'Redis 2.4' => ['(db 15) ', 15, null],
            'IPv4' => ['[15 127.0.0.1:37265] ', 15, '127.0.0.1:37265'],
            'IPv6' => ['[15 [::1]:37265] ', 15, '[::1]:37265'],
            'Unix socket' => ['[15 unix:/tmp/redis.sock] ', 15, 'unix:/tmp/redis.sock'],
            'Lua script' => ['[15 lua] ', 15, 'lua'],
            'Missing metadata' => ['', 0, null],
        ];
    }

    /**
     * @group disconnected
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMalformedMonitorLineDoesNotSearchForMetadata(): void
    {
        $this->usePcreInterpreterWithLowBacktrackLimit();

        $message = str_repeat('[1 ', 22000);
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($message);
        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $monitor = (new Client($connection))->monitor();
        $payload = $monitor->current();
        $error = preg_last_error();

        $this->assertSame(PREG_NO_ERROR, $error);
        $this->assertSame(0.0, $payload->timestamp);
        $this->assertSame(0, $payload->database);
        $this->assertNull($payload->client);
    }

    /**
     * Metadata can only be matched directly after the timestamp. This is what
     * keeps the extra search preg_replace_callback() performs after reaching
     * its limit from scanning arguments, which no timing-free test can observe.
     *
     * @group disconnected
     * @dataProvider adversarialMonitorMessages
     */
    public function testDoesNotReadMetadataFromArguments(string $metadata, int $database, ?string $address): void
    {
        $arguments = '"key" "x [5 spoof] (db 5) y"';
        $message = '1323367530.939137 ' . $metadata . '"SET" ' . $arguments;

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn($message);
        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $monitor = (new Client($connection))->monitor();
        $payload = $monitor->current();

        $this->assertSame($database, $payload->database);
        $this->assertSame($address, $payload->client);
        $this->assertSame('SET', $payload->command);
        $this->assertSame($arguments, $payload->arguments);
    }

    /**
     * PHP decides whether to JIT a pattern when first compiling it, and PHPUnit
     * has no annotation for ini settings, so callers must run in a separate process.
     */
    private function usePcreInterpreterWithLowBacktrackLimit(): void
    {
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '1000');
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testMonitorAgainstRedisServer(): void
    {
        $parameters = [
            'host' => constant('REDIS_SERVER_HOST'),
            'port' => constant('REDIS_SERVER_PORT'),
            'database' => constant('REDIS_SERVER_DBNUM'),
            'password' => getenv('REDIS_PASSWORD') ?: constant('REDIS_PASSWORD'),
            // Prevents suite from handing on broken test
            'read_write_timeout' => 2,
        ];

        $echoed = [];

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

        $this->assertSame(['message1', 'message2', 'QUIT'], $echoed);
        $this->assertFalse($monitor->valid());
        $this->assertEquals('PONG', $consumer->ping());
    }
}
