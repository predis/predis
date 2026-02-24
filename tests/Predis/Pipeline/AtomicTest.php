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
use Predis\Connection\Parameters;
use Predis\Response;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\TimeoutException;
use PredisTestCase;

class AtomicTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testPipelineWithSingleConnection(): void
    {
        $pong = new Response\Status('PONG');
        $queued = new Response\Status('QUEUED');
        $buffer = (new PING())->serializeCommand() . (new PING())->serializeCommand() . (new PING())->serializeCommand();

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('MULTI')],
                [$this->isRedisCommand('EXEC')]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                [$pong, $pong, $pong]
            );
        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer);
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls(
                $queued,
                $queued,
                $queued
            );

        $connection
            ->expects($this->exactly(4))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame([$pong, $pong, $pong], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnAbortedTransaction(): void
    {
        $buffer = (new PING())->serializeCommand() . (new PING())->serializeCommand() . (new PING())->serializeCommand();
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('The underlying transaction has been aborted by the server');

        $queued = new Response\Status('QUEUED');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('MULTI')],
                [$this->isRedisCommand('EXEC')]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                null
            );
        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer);
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls(
                $queued,
                $queued,
                $queued
            );
        $connection
            ->expects($this->exactly(3))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $pipeline->execute();
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithErrorInTransaction(): void
    {
        $buffer = (new PING())->serializeCommand() . (new PING())->serializeCommand() . (new PING())->serializeCommand();
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR Test error');

        $queued = new Response\Status('QUEUED');
        $error = new Response\Error('ERR Test error');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('MULTI')],
                [$this->isRedisCommand('DISCARD')]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                new Response\Status('OK')
            );
        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer);
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls(
                $queued,
                $queued,
                $error
            );
        $connection
            ->expects($this->exactly(3))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $pipeline->execute();
    }

    /**
     * @group disconnected
     */
    public function testThrowsServerExceptionOnResponseErrorByDefault(): void
    {
        $buffer = (new PING())->serializeCommand() . (new PING())->serializeCommand();
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR Test error');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('MULTI')],
                [$this->isRedisCommand('DISCARD')]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                new Response\Status('OK')
            );
        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer);
        $connection
            ->expects($this->once())
            ->method('readResponse')
            ->willReturn(
                new Response\Error('ERR Test error')
            );
        $connection
            ->expects($this->exactly(3))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();

        $pipeline->execute();
    }

    /**
     * @group disconnected
     */
    public function testReturnsResponseErrorWithClientExceptionsSetToFalse(): void
    {
        $buffer = (new PING())->serializeCommand() . (new PING())->serializeCommand() . (new PING())->serializeCommand();
        $pong = new Response\Status('PONG');
        $queued = new Response\Status('QUEUED');
        $error = new Response\Error('ERR Test error');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('MULTI')],
                [$this->isRedisCommand('EXEC')]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                [$pong, $pong, $error]
            );
        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer);
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls(
                $queued,
                $queued,
                $queued
            );

        $connection
            ->expects($this->exactly(4))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Atomic(new Client($connection, ['exceptions' => false]));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame([$pong, $pong, $error], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testExecutorWithAggregateConnection(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage("The class 'Predis\Pipeline\Atomic' does not support aggregate connections");

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();

        $pipeline->execute();
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
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('MULTI')],
                [$this->isRedisCommand('EXEC')]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                ['PONG', 'PONG', 'PONG']
            );

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
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                "+QUEUED\r\n",
                "+QUEUED\r\n",
                "+QUEUED\r\n"
            );

        $mockConnection
            ->expects($this->atLeast(3))
            ->method('disconnect')
            ->withAnyParameters();

        $mockConnection
            ->expects($this->exactly(4))
            ->method('getParameters')
            ->willReturn($parameters);

        $pipeline = new Atomic(new Client($mockConnection));

        $responses = $pipeline->execute(function (Pipeline $pipe) {
            $pipe->ping();
            $pipe->ping();
            $pipe->ping();
        });

        $this->assertEquals(['PONG', 'PONG', 'PONG'], $responses);
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
