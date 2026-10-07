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

use Predis\Client;
use Predis\Command\CommandInterface;
use Predis\Command\Redis\PING;
use Predis\Connection\ConnectionException;
use Predis\NotSupportedException;
use PredisTestCase;

class ConnectionErrorProofTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testReturnsExceptionForEachCommandOnWriteFailureWithSingleConnection(): void
    {
        $buffer = (new PING())->serializeCommand() . (new PING())->serializeCommand() . (new PING())->serializeCommand();

        $connection = $this->getMockConnection('tcp://127.0.0.1:7001');
        $exception = new ConnectionException($connection, 'Connection failed');

        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer)
            ->willThrowException($exception);
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $pipeline = new ConnectionErrorProof(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame([$exception, $exception, $exception], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testReturnsExceptionOfEachFailedConnectionWithClusterConnection(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $exception1 = new ConnectionException($connection1, 'Connection to node 1 failed');
        $connection1
            ->expects($this->exactly(2))
            ->method('write');
        $connection1
            ->expects($this->once())
            ->method('readResponse')
            ->willThrowException($exception1);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $exception2 = new ConnectionException($connection2, 'Connection to node 2 failed');
        $connection2
            ->expects($this->exactly(2))
            ->method('write');
        $connection2
            ->expects($this->once())
            ->method('readResponse')
            ->willThrowException($exception2);

        $cluster = $this->getMockClusterConnection(['foo' => $connection1, 'bar' => $connection2]);

        $pipeline = new ConnectionErrorProof(new Client($cluster));

        $pipeline->get('foo');
        $pipeline->get('bar');
        $pipeline->get('foo');
        $pipeline->get('bar');

        $this->assertSame([$exception1, $exception2, $exception1, $exception2], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testReadsResponsesFromHealthyConnectionsWhenOneFailsWithClusterConnection(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $exception = new ConnectionException($connection1, 'Connection to node 1 failed');
        $connection1
            ->expects($this->exactly(2))
            ->method('write');
        $connection1
            ->expects($this->once())
            ->method('readResponse')
            ->willThrowException($exception);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->exactly(2))
            ->method('write');
        $connection2
            ->expects($this->exactly(2))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls('value1', 'value2');

        $cluster = $this->getMockClusterConnection(['foo' => $connection1, 'bar' => $connection2]);

        $pipeline = new ConnectionErrorProof(new Client($cluster));

        $pipeline->get('foo');
        $pipeline->get('bar');
        $pipeline->get('foo');
        $pipeline->get('bar');

        $this->assertSame([$exception, 'value1', $exception, 'value2'], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnUnsupportedConnection(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches('/^The connection class \'.+\' is not supported\.$/');

        $connection = $this->getMockBuilder('Predis\Connection\Replication\ReplicationInterface')->getMock();

        $pipeline = new ConnectionErrorProof(new Client($connection));

        $pipeline->ping();
        $pipeline->execute();
    }

    /**
     * Returns a mocked cluster connection routing commands by their first key.
     *
     * @param array $connectionsByKey Node connections indexed by key
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Predis\Connection\Cluster\ClusterInterface
     */
    private function getMockClusterConnection(array $connectionsByKey)
    {
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();
        $cluster
            ->expects($this->atLeastOnce())
            ->method('getConnectionByCommand')
            ->willReturnCallback(static function (CommandInterface $command) use ($connectionsByKey) {
                return $connectionsByKey[$command->getArgument(0)];
            });

        return $cluster;
    }
}
