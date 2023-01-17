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

namespace Predis\Pipeline;

use Exception;
use InvalidArgumentException;
use Predis\Client;
use Predis\ClientException;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\Response;
use PredisTestCase;
use stdClass;

/**
 *
 */
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
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(3))
            ->method('writeRequest');
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnCallback($this->getReadCallback());

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
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(4))
            ->method('writeRequest');
        $connection
            ->expects($this->exactly(4))
            ->method('readResponse')
            ->willReturnCallback($this->getReadCallback());

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
        $pong = new Response\Status('PONG');

        $connection = $this->getMockBuilder('Predis\Connection\Replication\ReplicationInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('switchToMaster');
        $connection
            ->expects($this->exactly(3))
            ->method('writeRequest');
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturn($pong);

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
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(4))
            ->method('writeRequest');
        $connection
            ->expects($this->exactly(4))
            ->method('readResponse')
            ->willReturnCallback($this->getReadCallback());

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

            list($echoed) = $command->getArguments();

            return $echoed;
        };
    }
}
