<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Client;
use Predis\Command\RawCommand;
use Predis\Connection\Resource\Exception\StreamInitException;
use Predis\Connection\Resource\StreamFactoryInterface;
use Predis\Consumer\Push\PushResponse;
use Predis\Protocol\ProtocolException;
use Predis\Response\Error as ErrorResponse;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * @method StreamConnection createConnection(bool $initialize = false)
 * @method StreamConnection createConnectionWithParams($parameters, $initialize = false)
 */
class StreamConnectionTest extends PredisConnectionTestCase
{
    /**
     * @var StreamFactoryInterface
     */
    private $mockStreamFactory;

    /**
     * @var StreamInterface
     */
    private $mockStream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStreamFactory = $this->getMockBuilder(StreamFactoryInterface::class)->getMock();
        $this->mockStream = $this->getMockBuilder(StreamInterface::class)->getMock();
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionClass(): string
    {
        return 'Predis\Connection\StreamConnection';
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInitializationCommandFailure(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');
        $this->expectExceptionMessage('Failed: ERR invalid DB index [tcp://127.0.0.1:6379]');

        $cmdSelect = RawCommand::create('SELECT', '1000');

        /** @var NodeConnectionInterface|MockObject */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['write', 'read'])
            ->setConstructorArgs([new Parameters()])
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('write');
        $connection
            ->method('read')
            ->willReturn(
                new ErrorResponse('ERR invalid DB index')
            );

        $connection->addConnectCommand($cmdSelect);
        $connection->connect();
    }

    /**
     * @group disconnected
     */
    public function testConnectWithNoConnectCommands(): void
    {
        $parameters = new Parameters();

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->never())
            ->method('write')
            ->withAnyParameters();

        $this->mockStream
            ->expects($this->never())
            ->method('read')
            ->withAnyParameters();

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $connection->connect();
    }

    /**
     * @group disconnected
     */
    public function testConnectWithConnectCommands(): void
    {
        $parameters = new Parameters();
        $command1 = new RawCommand('AUTH', [12345, 12345]);
        $command2 = new RawCommand('SELECT', [10]);
        $command3 = new RawCommand('CLIENT', ['SETNAME', 'predis']);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->exactly(3))
            ->method('write')
            ->withConsecutive(
                [$command1->serializeCommand()],
                [$command2->serializeCommand()],
                [$command3->serializeCommand()]
            )
            ->willReturnOnConsecutiveCalls(
                strlen($command1->serializeCommand()),
                strlen($command2->serializeCommand()),
                strlen($command3->serializeCommand())
            );

        $this->mockStream
            ->expects($this->exactly(3))
            ->method('read')
            ->with(-1)
            ->willReturn('+OK\r\n');

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $connection->addConnectCommand($command1);
        $connection->addConnectCommand($command2);
        $connection->addConnectCommand($command3);

        $connection->connect();
    }

    /**
     * @group disconnected
     */
    public function testDisconnectDoNothingOnAlreadyDisconnectedConnection(): void
    {
        $parameters = new Parameters();

        $this->mockStreamFactory
            ->expects($this->never())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->never())
            ->method('close')
            ->withAnyParameters();

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $connection->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testDisconnectOnAlreadyConnectedConnection(): void
    {
        $parameters = new Parameters();

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('close')
            ->withAnyParameters();

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $connection->connect();
        $connection->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testWriteWholeBufferAtOnce(): void
    {
        $parameters = new Parameters();
        $command = new RawCommand('PING');

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('write')
            ->with($command->serializeCommand())
            ->willReturn(strlen($command->serializeCommand()));

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);
        $connection->write($command->serializeCommand());
    }

    /**
     * @group disconnected
     */
    public function testWriteBufferByChunks(): void
    {
        $parameters = new Parameters();
        $command = new RawCommand('PING');
        $firstChunk = substr($command->serializeCommand(), 0, strlen($command->serializeCommand()) / 2);
        $secondChunk = substr($command->serializeCommand(), strlen($command->serializeCommand()) / 2);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive([$command->serializeCommand()], [$secondChunk])
            ->willReturnOnConsecutiveCalls(strlen($firstChunk), strlen($secondChunk));

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);
        $connection->write($command->serializeCommand());
    }

    /**
     * @group disconnected
     */
    public function testWriteThrowsExceptionOnNonSuccessfulStreamWrite(): void
    {
        $parameters = new Parameters();
        $command = new RawCommand('PING');

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('write')
            ->with($command->serializeCommand())
            ->willThrowException(new RuntimeException('Error from stream during write', 1));

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Error while writing bytes to the server. [tcp://127.0.0.1:6379]');

        $connection->write($command->serializeCommand());
    }

    /**
     * @dataProvider simpleDataTypesProvider
     * @group disconnected
     */
    public function testReadSimpleDataTypes(string $payload, $expectedResponse): void
    {
        $parameters = new Parameters(['protocol' => 3]);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with($parameters)
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('read')
            ->with(-1)
            ->willReturn($payload);

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $this->assertEquals($expectedResponse, $connection->read());
    }

    /**
     * @dataProvider aggregateDataTypesProvider
     * @group disconnected
     */
    public function testReadAggregateDataTypes(int $count, array $lengths, array $processedLines, $expectedResponse): void
    {
        $parameters = new Parameters(['protocol' => 3]);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with($parameters)
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->exactly($count))
            ->method('read')
            ->withConsecutive(...$lengths)
            ->willReturnOnConsecutiveCalls(...$processedLines);

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $this->assertEquals($expectedResponse, $connection->read());
    }

    /**
     * @group disconnected
     */
    public function testReadAggregateDataTypesThrowsExceptionOnBrokenChunk(): void
    {
        $parameters = new Parameters(['protocol' => 3]);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with($parameters)
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->exactly(2))
            ->method('read')
            ->withConsecutive([-1], [8])
            ->willReturnOnConsecutiveCalls(
                "$6\r\nfoobar\r\n",
                $this->throwException(new RuntimeException('Error while reading', 1))
            );

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Error while reading bytes from the server. [tcp://127.0.0.1:6379]');

        $connection->read();
    }

    /**
     * @group disconnected
     */
    public function testReadThrowsExceptionOnUnknownDataType(): void
    {
        $parameters = new Parameters(['protocol' => 3]);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with($parameters)
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('read')
            ->with(-1)
            ->willReturn("@wrongtype\r\n");

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $this->expectException(ProtocolException::class);
        $this->expectExceptionMessage("Unknown response prefix: '@'. [tcp://127.0.0.1:6379]");

        $connection->read();
    }

    /**
     * @group disconnected
     */
    public function testReadThrowsExceptionOnInvalidBrokenStreamTransport(): void
    {
        $parameters = new Parameters(['protocol' => 3]);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with($parameters)
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('read')
            ->with(-1)
            ->willThrowException(new RuntimeException('Error on read', 1));

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Error while reading line from the server. [tcp://127.0.0.1:6379]');

        $connection->read();
    }

    /**
     * @group disconnected
     */
    public function testReadThrowsExceptionOnNonReadableStream(): void
    {
        $parameters = new Parameters(['protocol' => 3]);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with($parameters)
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('read')
            ->with(-1)
            ->willThrowException(new RuntimeException('Cannot read from non-readable stream'));

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot read from non-readable stream');

        $connection->read();
    }

    /**
     * @group disconnected
     */
    public function testReadThrowsExceptionOnEOF(): void
    {
        $parameters = new Parameters(['protocol' => 3]);

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with($parameters)
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('eof')
            ->willReturn(true);

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is already at the end');

        $connection->read();
    }

    /**
     * @group disconnected
     */
    public function testWriteRequest(): void
    {
        $parameters = new Parameters();
        $command = new RawCommand('PING');

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('write')
            ->with($command->serializeCommand())
            ->willReturn(strlen($command->serializeCommand()));

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);
        $connection->writeRequest($command);
    }

    /**
     * @group disconnected
     */
    public function testHasDataToRead(): void
    {
        $parameters = new Parameters();

        $this->mockStreamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(new Parameters())
            ->willReturn($this->mockStream);

        $this->mockStream
            ->expects($this->once())
            ->method('eof')
            ->willReturn(false);

        $connection = new StreamConnection($parameters, $this->mockStreamFactory);
        $this->assertTrue($connection->hasDataToRead());
    }

    public function simpleDataTypesProvider(): array
    {
        return [
            'simple_string' => [
                "+OK\r\n",
                'OK',
            ],
            'error' => [
                "-Error message\r\n",
                new ErrorResponse('Error message'),
            ],
            'integer' => [
                ":1000\r\n",
                1000,
            ],
            'null' => [
                "_\r\n",
                null,
            ],
            'double' => [
                ",1.23\r\n",
                1.23,
            ],
            'boolean' => [
                "#f\r\n",
                false,
            ],
            'big_number' => [
                "(3492890328409238509324850943850943825024385\r\n",
                3492890328409238509324850943850943825024385,
            ],
        ];
    }

    public function aggregateDataTypesProvider(): array
    {
        return [
            'bulk_string' => [
                2,
                [[-1], [8]],
                ["$6\r\nfoobar\r\n", "foobar\r\n"],
                'foobar',
            ],
            'array' => [
                5,
                [[-1], [-1], [5], [-1], [5]],
                [
                    "*2\r\n$3\r\nfoo\r\n$3\r\nbar\r\n",
                    "$3\r\nfoo\r\n",
                    "foo\r\n",
                    "$3\r\nbar\r\n",
                    "bar\r\n",
                ],
                ['foo', 'bar'],
            ],
            'verbatim_string' => [
                2,
                [[-1], [17]],
                ["=15\r\ntxt:Some string\r\n", "txt:Some string\r\n"],
                'Some string',
            ],
            'blob_error' => [
                2,
                [[-1], [23]],
                ["!21\r\nSYNTAX invalid syntax\r\n", "SYNTAX invalid syntax\r\n"],
                new ErrorResponse('SYNTAX invalid syntax'),
            ],
            'map' => [
                5,
                [[-1], [-1], [-1], [-1], [-1]],
                [
                    "%2\r\n+first\r\n:1\r\n+second\r\n:2\r\n",
                    "+first\r\n",
                    ":1\r\n",
                    "+second\r\n",
                    ":2\r\n",
                ],
                ['first' => 1, 'second' => 2],
            ],
            'set' => [
                6,
                [[-1], [-1], [-1], [-1], [-1], [-1]],
                [
                    "~5\r\n+orange\r\n+apple\r\n#t\r\n:100\r\n:999\r\n",
                    "+orange\r\n",
                    "+apple\r\n",
                    "#t\r\n",
                    ":100\r\n",
                    ":999\r\n",
                ],
                ['orange', 'apple', true, 100, 999],
            ],
            'push' => [
                4,
                [[-1], [-1], [-1], [-1]],
                [
                    ">3\r\n+message\r\n+somechannel\r\n+this is the message\r\n",
                    "+message\r\n",
                    "+somechannel\r\n",
                    "+this is the message\r\n",
                ],
                new PushResponse(['message', 'somechannel', 'this is the message']),
            ],
        ];
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithFalseLikeValues(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 0]);
        $this->assertNonPersistentConnection($connection1->getResource()->detach());

        $connection2 = $this->createConnectionWithParams(['persistent' => false]);
        $this->assertNonPersistentConnection($connection2->getResource()->detach());

        $connection3 = $this->createConnectionWithParams(['persistent' => '0']);
        $this->assertNonPersistentConnection($connection3->getResource()->detach());

        $connection4 = $this->createConnectionWithParams(['persistent' => 'false']);
        $this->assertNonPersistentConnection($connection4->getResource()->detach());
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithTrueLikeValues(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 1]);
        $this->assertPersistentConnection($connection1->getResource()->detach());

        $connection2 = $this->createConnectionWithParams(['persistent' => true]);
        $this->assertPersistentConnection($connection2->getResource()->detach());

        $connection3 = $this->createConnectionWithParams(['persistent' => '1']);
        $this->assertPersistentConnection($connection3->getResource()->detach());

        $connection4 = $this->createConnectionWithParams(['persistent' => 'true']);
        $this->assertPersistentConnection($connection4->getResource()->detach());

        $connection1->disconnect();
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentConnectionsToSameNodeShareResource(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => true]);
        $connection2 = $this->createConnectionWithParams(['persistent' => true]);

        $this->assertPersistentConnection($connection1->getResource()->detach());
        $this->assertPersistentConnection($connection2->getResource()->detach());

        $this->assertEquals($connection1->getResource(), $connection2->getResource());

        $connection1->disconnect();
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentConnectionsToSameNodeDoNotShareResourceUsingDifferentPersistentID(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 'conn1']);
        $connection2 = $this->createConnectionWithParams(['persistent' => 'conn2']);

        $this->assertPersistentConnection($connection1->getResource()->detach());
        $this->assertPersistentConnection($connection2->getResource()->detach());

        $this->assertNotSame($connection1->getResource(), $connection2->getResource());
    }

    /**
     * @group connected
     * @group slow
     */
    public function testThrowsExceptionOnConnectionTimeout(): void
    {
        // FACTORY TEST
        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessageMatches('/.* \[tcp:\/\/169.254.10.10:6379\]/');

        $connection = $this->createConnectionWithParams([
            'host' => '169.254.10.10',
            'timeout' => 0.1,
        ], false);

        $connection->connect();
    }

    /**
     * @group connected
     * @group slow
     */
    public function testThrowsExceptionOnConnectionTimeoutIPv6(): void
    {
        // FACTORY TEST
        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessageMatches('/.* \[tcp:\/\/\[0:0:0:0:0:ffff:a9fe:a0a\]:6379\]/');

        $connection = $this->createConnectionWithParams([
            'host' => '0:0:0:0:0:ffff:a9fe:a0a',
            'timeout' => 0.1,
        ], false);

        $connection->connect();
    }

    /**
     * @group connected
     * @group slow
     */
    public function testThrowsExceptionOnUnixDomainSocketNotFound(): void
    {
        // FACTORY TEST
        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessageMatches('/.* \[unix:\/tmp\/nonexistent\/redis\.sock]/');

        $connection = $this->createConnectionWithParams([
            'scheme' => 'unix',
            'path' => '/tmp/nonexistent/redis.sock',
        ], false);

        $connection->connect();
    }

    /**
     * @medium
     * @group connected
     */
    public function testThrowsExceptionOnProtocolDesynchronizationErrors(): void
    {
        $this->expectException('Predis\Protocol\ProtocolException');

        $connection = $this->createConnection();
        $stream = $connection->getResource();

        $connection->writeRequest($this->getCommandFactory()->create('ping'));
        $stream->read(1);

        $connection->read();
    }

    /**
     * @group connected
     */
    public function testTcpNodelayParameterSetsContextFlagWhenTrue()
    {
        $connection = $this->createConnectionWithParams(['tcp_nodelay' => true]);
        $options = stream_context_get_options($connection->getResource()->detach());

        $this->assertIsArray($options);
        $this->assertArrayHasKey('socket', $options);
        $this->assertArrayHasKey('tcp_nodelay', $options['socket']);
        $this->assertTrue($options['socket']['tcp_nodelay']);
    }

    /**
     * @group connected
     */
    public function testTcpNodelayParameterDoesNotSetContextFlagWhenFalse()
    {
        $connection = $this->createConnectionWithParams(['tcp_nodelay' => false]);
        $options = stream_context_get_options($connection->getResource()->detach());

        $this->assertIsArray($options);
        $this->assertArrayHasKey('socket', $options);
        $this->assertArrayHasKey('tcp_nodelay', $options['socket']);
        $this->assertFalse($options['socket']['tcp_nodelay']);
    }

    /**
     * @group connected
     */
    public function testTcpDelayContextFlagIsNotSetByDefault()
    {
        $connection = $this->createConnectionWithParams([]);
        $options = stream_context_get_options($connection->getResource()->detach());

        $this->assertIsArray($options);
        $this->assertArrayHasKey('socket', $options);
        $this->assertArrayHasKey('tcp_nodelay', $options['socket']);
        $this->assertFalse($options['socket']['tcp_nodelay']);
    }

    /**
     * @group connected
     * @requiresRedisVersion < 7.0.0
     */
    public function testConnectDoNotThrowsExceptionOnClientCommandError(): void
    {
        $connection = $this->createConnectionWithParams([]);
        $connection->addConnectCommand(
            new RawCommand('CLIENT', ['SETINFO', 'LIB-NAME', 'predis'])
        );
        $connection->addConnectCommand(
            new RawCommand('CLIENT', ['SETINFO', 'LIB-VER', Client::VERSION])
        );

        $connection->connect();
        $this->assertTrue(true);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testSetClientIdOnResp2Connection(): void
    {
        $connection = $this->createConnectionWithParams([]);
        $connection->addConnectCommand(
            new RawCommand('HELLO', [2])
        );
        $connection->connect();

        $this->assertNotNull($connection->getClientId());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testDoNotSetClientIdOnResp2ConnectionIfNotHelloCommand(): void
    {
        $connection = $this->createConnectionWithParams([]);
        $connection->addConnectCommand(
            new RawCommand('INFO')
        );
        $connection->connect();

        $this->assertNull($connection->getClientId());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testSetClientIdOnResp3Connection(): void
    {
        $connection = $this->createConnectionWithParams(['protocol' => 3]);
        $connection->addConnectCommand(
            new RawCommand('HELLO', [3])
        );
        $connection->connect();

        $this->assertNotNull($connection->getClientId());
    }

    /**
     * @group connected
     * @return void
     */
    public function testConnectionDoesNotThrowsExceptionOnClientCommandFail(): void
    {
        $failedCommand = new RawCommand('CLIENT', ['FOOBAR']);

        $connection = $this->createConnection();
        $connection->addConnectCommand($failedCommand);

        $connection->connect();

        $this->assertTrue(true);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testConnectionRetriesOnFailingHelloCommand(): void
    {
        $failedCommand = new RawCommand('HELLO', ['FOOBAR']);

        $connection = $this->createConnection();
        $connection->addConnectCommand($failedCommand);

        $connection->connect();

        $clientName = $connection->executeCommand(new RawCommand('CLIENT', ['GETNAME']));

        $this->assertSame('predis', $clientName);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testConnectionRetriesOnFailingHelloCommandButFailsOnAuth(): void
    {
        $failedCommand = new RawCommand('HELLO', ['FOOBAR', 'AUTH', 'foobar']);

        $connection = $this->createConnection();
        $connection->addConnectCommand($failedCommand);

        $this->expectException(ConnectionException::class);
        $connection->connect();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testGetInitCommandsReturnsGivenInitCommands(): void
    {
        $command = new RawCommand('HELLO', [3]);

        $connection = $this->createConnection();
        $connection->addConnectCommand($command);

        $initCommands = $connection->getInitCommands();

        $this->assertInstanceOf(RawCommand::class, $initCommands[0]);
        $this->assertSame('HELLO', $initCommands[0]->getId());
    }
}
