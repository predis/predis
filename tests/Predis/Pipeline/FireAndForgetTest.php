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

namespace Predis\Pipeline;

use Exception;
use Predis\Client;
use Predis\ClientInterface;
use Predis\Command\Redis\PING;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\Parameters;
use Predis\Connection\Replication\MasterSlaveReplication;
use Predis\Response;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\TimeoutException;
use PredisTestCase;

class FireAndForgetTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testPipelineWithSingleConnection(): void
    {
        $buffer = (new PING())->serializeCommand() . (new PING())->serializeCommand() . (new PING())->serializeCommand();
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer);
        $connection
            ->expects($this->never())
            ->method('readResponse');
        $connection
            ->expects($this->exactly(1))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new FireAndForget(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertEmpty($pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testSwitchesToMasterWithReplicationConnection(): void
    {
        $nodeConnection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $nodeConnection
            ->expects($this->exactly(3))
            ->method('write')
            ->with((new PING())->serializeCommand());

        $connection = $this->getMockBuilder('Predis\Connection\Replication\ReplicationInterface')
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('switchToMaster');
        $connection
            ->expects($this->exactly(3))
            ->method('getConnectionByCommand')
            ->willReturn($nodeConnection);
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new FireAndForget(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertEmpty($pipeline->execute());
    }

    /**
     * @group disconnected
     * @throws Exception
     */
    public function testRetryStandalonePipelineOnRetryableErrors(): void
    {
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
        ]);
        $mockConnection = $this->getMockConnection();

        $mockConnection
            ->expects($this->exactly(4))
            ->method('write')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new TimeoutException($mockConnection)),
                $this->throwException(new TimeoutException($mockConnection)),
                $this->throwException(new TimeoutException($mockConnection)),
                1000
            );

        $mockConnection
            ->expects($this->atLeast(3))
            ->method('disconnect')
            ->withAnyParameters();

        $mockConnection
            ->expects($this->exactly(1))
            ->method('getParameters')
            ->willReturn($parameters);

        $pipeline = new FireAndForget(new Client($mockConnection));

        $pipeline->execute(function (Pipeline $pipe) {
            $pipe->ping();
            $pipe->ping();
            $pipe->ping();
        });
    }

    /**
     * @group disconnected
     * @throws Exception
     */
    public function testRetryClusterPipelineOnRetryableErrors(): void
    {
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
        ]);
        $mockConnection = $this->getMockConnection();
        $mockClusterConnection = $this->getMockBuilder(RedisCluster::class)
            ->disableOriginalConstructor()->getMock();

        $mockConnection
            ->expects($this->exactly(6))
            ->method('write')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new TimeoutException($mockConnection)),
                $this->throwException(new TimeoutException($mockConnection)),
                $this->throwException(new TimeoutException($mockConnection)),
                1000,
                1000,
                1000
            );

        $mockConnection
            ->expects($this->atLeast(3))
            ->method('disconnect')
            ->withAnyParameters();

        $mockClusterConnection
            ->expects($this->exactly(5))
            ->method('getParameters')
            ->willReturn($parameters);

        $mockClusterConnection
            ->expects($this->exactly(6))
            ->method('getConnectionByCommand')
            ->willReturn($mockConnection);

        $pipeline = new FireAndForget(new Client($mockClusterConnection));

        $pipeline->execute(function (Pipeline $pipe) {
            $pipe->ping();
            $pipe->ping();
            $pipe->ping();
        });
    }

    /**
     * @group disconnected
     * @throws Exception
     */
    public function testRetryReplicationPipelineOnRetryableErrors(): void
    {
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
        ]);
        $mockConnection = $this->getMockConnection();
        $mockReplicationConnection = $this->getMockBuilder(MasterSlaveReplication::class)
            ->disableOriginalConstructor()->getMock();

        $mockConnection
            ->expects($this->exactly(6))
            ->method('write')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new TimeoutException($mockConnection)),
                $this->throwException(new TimeoutException($mockConnection)),
                $this->throwException(new TimeoutException($mockConnection)),
                1000,
                1000,
                1000
            );

        $mockConnection
            ->expects($this->atLeast(3))
            ->method('disconnect')
            ->withAnyParameters();

        $mockReplicationConnection
            ->expects($this->exactly(5))
            ->method('getParameters')
            ->willReturn($parameters);

        $mockReplicationConnection
            ->expects($this->exactly(6))
            ->method('getConnectionByCommand')
            ->willReturn($mockConnection);

        $pipeline = new FireAndForget(new Client($mockReplicationConnection));

        $pipeline->execute(function (Pipeline $pipe) {
            $pipe->ping();
            $pipe->ping();
            $pipe->ping();
        });
    }

    /**
     * @group connected
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 6.2.0
     */
    public function testClusterExecutePipeline(): void
    {
        $pipeline = new FireAndForget($this->createClient());

        $pipeline->set('foo', 'bar');
        $pipeline->get('foo');
        $pipeline->set('bar', 'foo');
        $pipeline->get('bar');
        $pipeline->set('baz', 'baz');
        $pipeline->get('baz');

        $this->assertEmpty($pipeline->execute());
    }

    /**
     * @group connected
     * @group relay-incompatible
     */
    public function testReplicationExecutesPipelineWithCRLFValues(): void
    {
        $parameters = $this->getDefaultParametersArray();

        $client = new Client(
            ["tcp://{$parameters['host']}:{$parameters['port']}?role=master&database={$parameters['database']}&password={$parameters['password']}"],
            ['replication' => 'predis']
        );

        $results = $client->pipeline(function (Pipeline $pipe) {
            $pipe->set('foo', "bar\r\nbaz");
            $pipe->get('foo');
        });

        $expectedResults = [
            new Response\Status('OK'),
            "bar\r\nbaz",
        ];

        $this->assertSameValues($expectedResults, $results);
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a client instance connected to the specified Redis server.
     *
     * @param array $parameters Additional connection parameters
     * @param array $options    Additional client options
     *
     * @return ClientInterface
     */
    protected function getClient(array $parameters = [], array $options = []): ClientInterface
    {
        return $this->createClient($parameters, $options);
    }
}
