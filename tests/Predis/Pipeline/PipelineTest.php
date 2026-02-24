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
use InvalidArgumentException;
use Predis\Client;
use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\Command\Redis\ECHO_;
use Predis\Command\Redis\PING;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\Factory;
use Predis\Connection\Parameters;
use Predis\Connection\Replication\MasterSlaveReplication;
use Predis\Connection\Resource\StreamFactoryInterface;
use Predis\Connection\StreamConnection;
use Predis\Replication\ReplicationStrategy;
use Predis\Response;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\TimeoutException;
use PredisTestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use stdClass;

class PipelineTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructor(): void
    {
        $client = new Client();
        $pipeline = new Pipeline($client);

        $this->assertSame($client, $pipeline->getClient());
    }

    /**
     * @group disconnected
     */
    public function testCallDoesNotSendCommandsWithoutExecute(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('writeRequest');
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->echo('one');
        $pipeline->echo('two');
        $pipeline->echo('three');
    }

    /**
     * @group disconnected
     */
    public function testCallReturnsPipelineForFluentInterface(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('writeRequest');
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $pipeline = new Pipeline(new Client($connection));

        $this->assertSame($pipeline, $pipeline->echo('one'));
        $this->assertSame($pipeline, $pipeline->echo('one')->echo('two')->echo('three'));
    }

    /**
     * @group disconnected
     */
    public function testDoesNotParseComplexResponseObjects(): void
    {
        $object = $this->getMockBuilder('Predis\Response\ResponseInterface')->getMock();

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('readResponse')
            ->willReturn($object);

        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->ping();

        $this->assertSame([$object], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testThrowsServerExceptionOnResponseErrorByDefault(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR Test error');

        $error = new Response\Error('ERR Test error');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('readResponse')
            ->willReturn($error);

        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();

        $pipeline->execute();
    }

    /**
     * @group disconnected
     */
    public function testReturnsResponseErrorWithClientExceptionsSetToFalse(): void
    {
        $error = $this->getMockBuilder('Predis\Response\ErrorInterface')->getMock();

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('readResponse')
            ->willReturn($error);

        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $client = new Client($connection, ['exceptions' => false]);

        $pipeline = new Pipeline($client);

        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame([$error, $error], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testExecuteReturnsPipelineForFluentInterface(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $command = $this->getCommandFactory()->create('echo', ['one']);

        $pipeline = new Pipeline(new Client($connection));

        $this->assertSame($pipeline, $pipeline->executeCommand($command));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandDoesNotSendCommandsWithoutExecute(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('writeRequest');
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $commands = $this->getCommandFactory();

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->executeCommand($commands->create('echo', ['one']));
        $pipeline->executeCommand($commands->create('echo', ['two']));
        $pipeline->executeCommand($commands->create('echo', ['three']));
    }

    /**
     * @group disconnected
     */
    public function testExecuteWithEmptyBuffer(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('writeRequest');
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $pipeline = new Pipeline(new Client($connection));

        $this->assertSame([], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testExecuteWithFilledBuffer(): void
    {
        $command1 = new ECHO_();
        $command1->setArguments(['one']);

        $command2 = new ECHO_();
        $command2->setArguments(['two']);

        $command3 = new ECHO_();
        $command3->setArguments(['three']);

        $buffer = $command1->serializeCommand() . $command2->serializeCommand() . $command3->serializeCommand();
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer);
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnCallback($this->getReadCallback());

        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->echo('one');
        $pipeline->echo('two');
        $pipeline->echo('three');

        $pipeline->flushPipeline();

        $this->assertSame(['one', 'two', 'three'], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testFlushWithFalseArgumentDiscardsBuffer(): void
    {
        $pipeline = new Pipeline(new Client());

        $pipeline->echo('one');
        $pipeline->echo('two');
        $pipeline->echo('three');

        $pipeline->flushPipeline(false);

        $this->assertSame([], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testFlushHandlesPartialBuffers(): void
    {
        $command1 = new ECHO_();
        $command1->setArguments(['one']);

        $command2 = new ECHO_();
        $command2->setArguments(['two']);

        $buffer1 = $command1->serializeCommand() . $command2->serializeCommand();

        $command3 = new ECHO_();
        $command3->setArguments(['three']);

        $command4 = new ECHO_();
        $command4->setArguments(['four']);

        $buffer2 = $command3->serializeCommand() . $command4->serializeCommand();

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive([$buffer1], [$buffer2]);
        $connection
            ->expects($this->exactly(4))
            ->method('readResponse')
            ->willReturnCallback($this->getReadCallback());

        $connection
            ->expects($this->exactly(4))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->echo('one');
        $pipeline->echo('two');
        $pipeline->flushPipeline();
        $pipeline->echo('three');
        $pipeline->echo('four');

        $this->assertSame(['one', 'two', 'three', 'four'], $pipeline->execute());
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
        $pong = new Response\Status('PONG');

        $connection = $this->getMockBuilder('Predis\Connection\Replication\ReplicationInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('switchToMaster');
        $connection
            ->expects($this->exactly(6))
            ->method('getConnectionByCommand')
            ->willReturn($nodeConnection);
        $nodeConnection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturn($pong);
        $connection
            ->expects($this->exactly(3))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame([$pong, $pong, $pong], $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testExecuteAcceptsCallableArgument(): void
    {
        $test = $this;
        $pipeline = new Pipeline(new Client());

        $callable = function (Pipeline $pipe) use ($test, $pipeline) {
            $test->assertSame($pipeline, $pipe);
            $pipe->flushPipeline(false);
        };

        $pipeline->execute($callable);
    }

    /**
     * @group disconnected
     */
    public function testExecuteDoesNotAcceptNonCallableArgument(): void
    {
        $this->expectException('InvalidArgumentException');

        $noncallable = new stdClass();

        $pipeline = new Pipeline(new Client());
        $pipeline->execute($noncallable);
    }

    /**
     * @group disconnected
     */
    public function testExecuteInsideCallableArgumentThrowsException(): void
    {
        $this->expectException('Predis\ClientException');

        $pipeline = new Pipeline(new Client());

        $pipeline->execute(function (Pipeline $pipe) {
            $pipe->execute();
        });
    }

    /**
     * @group disconnected
     */
    public function testExecuteWithCallableArgumentRunsPipelineInCallable(): void
    {
        $command1 = new ECHO_();
        $command1->setArguments(['one']);

        $command2 = new ECHO_();
        $command2->setArguments(['two']);

        $command3 = new ECHO_();
        $command3->setArguments(['three']);

        $command4 = new ECHO_();
        $command4->setArguments(['four']);

        $buffer = $command1->serializeCommand()
            . $command2->serializeCommand()
            . $command3->serializeCommand()
            . $command4->serializeCommand();

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('write')
            ->with($buffer);
        $connection
            ->expects($this->exactly(4))
            ->method('readResponse')
            ->willReturnCallback($this->getReadCallback());
        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $pipeline = new Pipeline(new Client($connection));

        $responses = $pipeline->execute(function (Pipeline $pipe) {
            $pipe->echo('one');
            $pipe->echo('two');
            $pipe->echo('three');
            $pipe->echo('four');
        });

        $this->assertSame(['one', 'two', 'three', 'four'], $responses);
    }

    /**
     * @group disconnected
     */
    public function testExecuteWithCallableArgumentHandlesExceptions(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('writeRequest');
        $connection
            ->expects($this->never())
            ->method('readResponse');

        $exception = null;
        $responses = null;

        $pipeline = new Pipeline(new Client($connection));

        try {
            $responses = $pipeline->execute(function (Pipeline $pipe) {
                $pipe->echo('one');
                $pipe->echo('two');
                throw new ClientException('TEST');
            });
        } catch (Exception $exception) {
            // NOOP
        }

        $this->assertInstanceOf('Predis\ClientException', $exception);
        $this->assertSame('TEST', $exception->getMessage());
        $this->assertNull($responses);
    }

    /**
     * @group disconnected
     * @throws Exception
     */
    public function testRetryStandalonePipelineOnRetryableErrors(): void
    {
        $mockStream = $this->getMockBuilder(StreamInterface::class)->getMock();
        $mockStreamFactory = $this->getMockBuilder(StreamFactoryInterface::class)->getMock();
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
        ]);

        $mockStream
            ->expects($this->atLeast(3))
            ->method('close')
            ->withAnyParameters();

        $mockStream
            ->expects($this->exactly(4))
            ->method('write')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new RuntimeException('', 2)),
                $this->throwException(new RuntimeException('', 2)),
                $this->throwException(new RuntimeException('', 2)),
                1000
            );

        $mockStream
            ->expects($this->exactly(3))
            ->method('read')
            ->withAnyParameters()
            ->willReturn("+PONG\r\n");

        $mockStreamFactory
            ->expects($this->exactly(4))
            ->method('createStream')
            ->withAnyParameters()
            ->willReturn($mockStream);

        $connection = new StreamConnection($parameters, $mockStreamFactory);
        $pipeline = new Pipeline(new Client($connection));

        $responses = $pipeline->execute(function (Pipeline $pipe) {
            $pipe->ping();
            $pipe->ping();
            $pipe->ping();
        });

        $this->assertEquals(['PONG', 'PONG', 'PONG'], $responses);
    }

    /**
     * @group disconnected
     * @throws Exception
     */
    public function testRetryClusterPipelineOnRetryableErrors(): void
    {
        $mockStream = $this->getMockBuilder(StreamInterface::class)->getMock();
        $mockStreamFactory = $this->getMockBuilder(StreamFactoryInterface::class)->getMock();
        $mockConnectionFactory = $this->getMockBuilder(Factory::class)->getMock();
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
        ]);

        $mockStream
            ->expects($this->atLeast(3))
            ->method('close')
            ->withAnyParameters();

        $mockStream
            ->expects($this->exactly(6))
            ->method('write')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new RuntimeException('', 2)),
                $this->throwException(new RuntimeException('', 2)),
                $this->throwException(new RuntimeException('', 2)),
                1000,
                1000,
                1000
            );

        $mockStream
            ->expects($this->exactly(3))
            ->method('read')
            ->withAnyParameters()
            ->willReturn("+OK\r\n");

        $mockStreamFactory
            ->expects($this->exactly(4))
            ->method('createStream')
            ->withAnyParameters()
            ->willReturn($mockStream);

        $streamConnection = new StreamConnection($parameters, $mockStreamFactory);
        $connection = new RedisCluster($mockConnectionFactory, $parameters);
        $connection->add($streamConnection);

        $pipeline = new Pipeline(new Client($connection));

        $responses = $pipeline->execute(function (Pipeline $pipe) {
            $pipe->set('key', 'value');
            $pipe->set('key', 'value');
            $pipe->set('key', 'value');
        });

        $this->assertEquals(['OK', 'OK', 'OK'], $responses);
    }

    /**
     * @group disconnected
     * @throws Exception
     */
    public function testExecutePipelineInvokesOnAggregateConnectionFailCallbackOnConnectionException(): void
    {
        $mockStream = $this->getMockBuilder(StreamInterface::class)->getMock();
        $mockStreamFactory = $this->getMockBuilder(StreamFactoryInterface::class)->getMock();
        $mockConnectionFactory = $this->getMockBuilder(Factory::class)->getMock();
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
        ]);

        $streamConnection = new StreamConnection($parameters, $mockStreamFactory);
        $connection = $this->getMockBuilder(RedisCluster::class)
            ->setConstructorArgs([$mockConnectionFactory, $parameters])
            ->onlyMethods(['getConnectionByCommand', 'remove', 'askSlotMap'])
            ->getMock();

        // Disable useClusterSlots to avoid askSlotMap() calls
        $connection->useClusterSlots = false;

        $mockStream
            ->expects($this->atLeast(3))
            ->method('close')
            ->withAnyParameters();

        $mockStream
            ->expects($this->exactly(6))
            ->method('write')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \Predis\Connection\ConnectionException($streamConnection, 'Connection failed')),
                $this->throwException(new \Predis\Connection\ConnectionException($streamConnection, 'Connection failed')),
                $this->throwException(new \Predis\Connection\ConnectionException($streamConnection, 'Connection failed')),
                1000,
                1000,
                1000
            );

        $mockStream
            ->expects($this->exactly(3))
            ->method('read')
            ->withAnyParameters()
            ->willReturn("+OK\r\n");

        $mockStreamFactory
            ->expects($this->exactly(4))
            ->method('createStream')
            ->withAnyParameters()
            ->willReturn($mockStream);

        $connection
            ->expects($this->exactly(9))
            ->method('getConnectionByCommand')
            ->willReturn($streamConnection);

        // Verify that remove() is called on the aggregate connection during retry
        $connection
            ->expects($this->exactly(3))
            ->method('remove')
            ->with($streamConnection);

        // Verify that askSlotMap() is NOT called since useClusterSlots is false
        $connection
            ->expects($this->never())
            ->method('askSlotMap');

        $pipeline = new Pipeline(new Client($connection));

        $responses = $pipeline->execute(function (Pipeline $pipe) {
            $pipe->set('key', 'value');
            $pipe->set('key', 'value');
            $pipe->set('key', 'value');
        });

        $this->assertEquals(['OK', 'OK', 'OK'], $responses);
    }

    /**
     * @group disconnected
     * @throws Exception
     */
    public function testExecutePipelineInvokesOnAggregateConnectionFailCallbackOnTimeoutException(): void
    {
        $mockStream = $this->getMockBuilder(StreamInterface::class)->getMock();
        $mockStreamFactory = $this->getMockBuilder(StreamFactoryInterface::class)->getMock();
        $mockConnectionFactory = $this->getMockBuilder(Factory::class)->getMock();
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
        ]);

        $streamConnection = new StreamConnection($parameters, $mockStreamFactory);
        $connection = $this->getMockBuilder(RedisCluster::class)
            ->setConstructorArgs([$mockConnectionFactory, $parameters])
            ->onlyMethods(['getConnectionByCommand', 'remove'])
            ->getMock();

        // Disable useClusterSlots to avoid askSlotMap() calls
        $connection->useClusterSlots = false;

        $mockStream
            ->expects($this->atLeast(3))
            ->method('close')
            ->withAnyParameters();

        $mockStream
            ->expects($this->exactly(6))
            ->method('write')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new TimeoutException($streamConnection, 0)),
                $this->throwException(new TimeoutException($streamConnection, 0)),
                $this->throwException(new TimeoutException($streamConnection, 0)),
                1000,
                1000,
                1000
            );

        $mockStream
            ->expects($this->exactly(3))
            ->method('read')
            ->withAnyParameters()
            ->willReturn("+OK\r\n");

        $mockStreamFactory
            ->expects($this->exactly(4))
            ->method('createStream')
            ->withAnyParameters()
            ->willReturn($mockStream);

        $connection
            ->expects($this->exactly(9))
            ->method('getConnectionByCommand')
            ->willReturn($streamConnection);

        // Verify that remove() is NOT called for TimeoutException
        $connection
            ->expects($this->never())
            ->method('remove');

        $pipeline = new Pipeline(new Client($connection));

        $responses = $pipeline->execute(function (Pipeline $pipe) {
            $pipe->set('key', 'value');
            $pipe->set('key', 'value');
            $pipe->set('key', 'value');
        });

        $this->assertEquals(['OK', 'OK', 'OK'], $responses);
    }

    /**
     * @group disconnected
     * @throws Exception
     */
    public function testRetryReplicationPipelineOnRetryableErrors(): void
    {
        $mockStream = $this->getMockBuilder(StreamInterface::class)->getMock();
        $mockStreamFactory = $this->getMockBuilder(StreamFactoryInterface::class)->getMock();
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
            'role' => 'master',
        ]);

        $mockStream
            ->expects($this->atLeast(3))
            ->method('close')
            ->withAnyParameters();

        $mockStream
            ->expects($this->exactly(6))
            ->method('write')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new RuntimeException('', 2)),
                $this->throwException(new RuntimeException('', 2)),
                $this->throwException(new RuntimeException('', 2)),
                1000,
                1000,
                1000
            );

        $mockStream
            ->expects($this->exactly(3))
            ->method('read')
            ->withAnyParameters()
            ->willReturn("+OK\r\n");

        $mockStreamFactory
            ->expects($this->exactly(4))
            ->method('createStream')
            ->withAnyParameters()
            ->willReturn($mockStream);

        $streamConnection = new StreamConnection($parameters, $mockStreamFactory);

        $connection = new MasterSlaveReplication(new ReplicationStrategy());
        $connection->add($streamConnection);

        $pipeline = new Pipeline(new Client($connection));

        $responses = $pipeline->execute(function (Pipeline $pipe) {
            $pipe->set('key', 'value');
            $pipe->set('key', 'value');
            $pipe->set('key', 'value');
        });

        $this->assertEquals(['OK', 'OK', 'OK'], $responses);
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testIntegrationWithFluentInterface(): void
    {
        $pipeline = $this->getClient()->pipeline();

        $results = $pipeline
            ->echo('one')
            ->echo('two')
            ->echo('three')
            ->execute();

        $this->assertSame(['one', 'two', 'three'], $results);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testIntegrationWithFluentInterfaceResp3(): void
    {
        $pipeline = $this->getClient(['protocol' => 3])->pipeline();

        $results = $pipeline
            ->echo('one')
            ->echo('two')
            ->echo('three')
            ->execute();

        $this->assertSame(['one', 'two', 'three'], $results);
    }

    /**
     * @group connected
     */
    public function testIntegrationWithCallableBlock(): void
    {
        $client = $this->getClient();

        $results = $client->pipeline(function (Pipeline $pipe) {
            $pipe->set('foo', 'bar');
            $pipe->get('foo');
        });

        $this->assertEquals(['OK', 'bar'], $results);
        $this->assertSame(1, $client->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testOutOfBandMessagesInsidePipeline(): void
    {
        $oob = null;
        $client = $this->getClient();

        $results = $client->pipeline(function (Pipeline $pipe) use (&$oob) {
            $pipe->set('foo', 'bar');
            $oob = $pipe->getClient()->echo('oob message');
            $pipe->get('foo');
        });

        $this->assertEquals(['OK', 'bar'], $results);
        $this->assertSame('oob message', $oob);
        $this->assertSame(1, $client->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testIntegrationWithClientExceptionInCallableBlock(): void
    {
        $exception = null;

        $client = $this->getClient();

        try {
            $client->pipeline(function (Pipeline $pipe) {
                $pipe->set('foo', 'bar');
                throw new ClientException('TEST');
            });
        } catch (Exception $exception) {
            // NOOP
        }

        $this->assertInstanceOf('Predis\ClientException', $exception);
        $this->assertSame('TEST', $exception->getMessage());
        $this->assertSame(0, $client->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testIntegrationWithServerExceptionInCallableBlock(): void
    {
        $exception = null;

        $client = $this->getClient();

        try {
            $client->pipeline(function (Pipeline $pipe) {
                $pipe->set('foo', 'bar');
                // LPUSH on a string key fails, but won't stop
                // the pipeline to send the commands.
                $pipe->lpush('foo', 'bar');
                $pipe->set('hoge', 'piyo');
            });
        } catch (Exception $exception) {
            // NOOP
        }

        $this->assertInstanceOf('Predis\Response\ServerException', $exception);
        $this->assertSame(1, $client->exists('foo'));
        $this->assertSame(1, $client->exists('hoge'));
    }

    /**
     * @group connected
     */
    public function testIntegrationWithServerErrorInCallableBlock(): void
    {
        $client = $this->getClient([], ['exceptions' => false]);

        $results = $client->pipeline(function (Pipeline $pipe) {
            $pipe->set('foo', 'bar');
            $pipe->lpush('foo', 'bar'); // LPUSH on a string key fails.
            $pipe->get('foo');
        });

        $this->assertEquals('OK', $results[0]);
        $this->assertInstanceOf('Predis\Response\Error', $results[1]);
        $this->assertSame('bar', $results[2]);
    }

    /**
     * @group connected
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 6.2.0
     */
    public function testClusterExecutePipeline(): void
    {
        $client = $this->getClient();

        $results = $client->pipeline(function (Pipeline $pipe) {
            $pipe->set('foo', 'bar');
            $pipe->set('bar', 'foo');
            $pipe->set('baz', 'baz');
            $pipe->get('foo');
            $pipe->get('bar');
            $pipe->get('baz');
        });

        $expectedResults = [
            new Response\Status('OK'),
            new Response\Status('OK'),
            new Response\Status('OK'),
            'bar',
            'foo',
            'baz',
        ];

        $this->assertSameValues($expectedResults, $results);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 6.2.0
     * @return void
     */
    public function testStandaloneRetryPipelineOnTimeoutException(): void
    {
        $client = $this->getClient([
            'retry' => new Retry(new ExponentialBackoff(100, 1000), 3),
            'read_write_timeout' => 0.1,
        ]);

        $this->expectException(TimeoutException::class);

        $client->pipeline(function (Pipeline $pipe) use (&$retries) {
            $pipe->incr('test_key');
            $pipe->blpop('foo', 3);
        });
        $this->assertEquals(3, $client->get('test_key'));
    }

    /**
     * @group connected
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 6.2.0
     * @return void
     */
    public function testClusterRetryPipelineOnTimeoutException(): void
    {
        $retries = 0;
        $client = $this->getClient([], [
            'parameters' => [
                'retry' => new Retry(new ExponentialBackoff(100, 1000), 3),
                'read_write_timeout' => 0.1,
            ],
        ]);

        $this->expectException(TimeoutException::class);

        $client->pipeline(function (Pipeline $pipe) use (&$retries) {
            ++$retries;
            $pipe->blpop('foo', 3);
        });
        $this->assertEquals(3, $retries);
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

    /**
     * Helper method returning a callback used to responses to ECHO command.
     *
     * @return callable
     */
    protected function getReadCallback(): callable
    {
        return function (CommandInterface $command) {
            if (($id = $command->getId()) !== 'ECHO') {
                throw new InvalidArgumentException("Expected ECHO, got {$id}");
            }

            [$echoed] = $command->getArguments();

            return $echoed;
        };
    }
}
