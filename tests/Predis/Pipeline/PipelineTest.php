<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Predis\Client;
use Predis\ClientException;
use Predis\Profile;
use Predis\Response;
use PredisTestCase;

/**
 *
 */
class PipelineTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructor()
    {
        $client = new Client();
        $pipeline = new Pipeline($client);

        $this->assertSame($client, $pipeline->getClient());
    }

    /**
     * @group disconnected
     */
    public function testCallDoesNotSendCommandsWithoutExecute()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->never())->method('writeRequest');
        $connection->expects($this->never())->method('readResponse');

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->echo('one');
        $pipeline->echo('two');
        $pipeline->echo('three');
    }

    /**
     * @group disconnected
     */
    public function testCallReturnsPipelineForFluentInterface()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->never())->method('writeRequest');
        $connection->expects($this->never())->method('readResponse');

        $pipeline = new Pipeline(new Client($connection));

        $this->assertSame($pipeline, $pipeline->echo('one'));
        $this->assertSame($pipeline, $pipeline->echo('one')->echo('two')->echo('three'));
    }

    /**
     * @group disconnected
     */
    public function testDoesNotParseComplexResponseObjects()
    {
        $object = $this->getMock('Predis\Response\ResponseInterface');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())
                   ->method('readResponse')
                   ->will($this->returnValue($object));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->ping();

        $this->assertSame(array($object), $pipeline->execute());
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR Test error
     */
    public function testThrowsServerExceptionOnResponseErrorByDefault()
    {
        $error = new Response\Error('ERR Test error');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())
                   ->method('readResponse')
                   ->will($this->returnValue($error));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();

        $pipeline->execute();
    }

    /**
     * @group disconnected
     */
    public function testReturnsResponseErrorWithClientExceptionsSetToFalse()
    {
        $error = $this->getMock('Predis\Response\ErrorInterface');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->exactly(2))
                   ->method('readResponse')
                   ->will($this->returnValue($error));

        $client = new Client($connection, array('exceptions' => false));

        $pipeline = new Pipeline($client);

        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame(array($error, $error), $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testExecuteReturnsPipelineForFluentInterface()
    {
        $profile = Profile\Factory::getDefault();
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $pipeline = new Pipeline(new Client($connection));
        $command = $profile->createCommand('echo', array('one'));

        $this->assertSame($pipeline, $pipeline->executeCommand($command));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandDoesNotSendCommandsWithoutExecute()
    {
        $profile = Profile\Factory::getDefault();

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->never())->method('writeRequest');
        $connection->expects($this->never())->method('readResponse');

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->executeCommand($profile->createCommand('echo', array('one')));
        $pipeline->executeCommand($profile->createCommand('echo', array('two')));
        $pipeline->executeCommand($profile->createCommand('echo', array('three')));
    }

    /**
     * @group disconnected
     */
    public function testExecuteWithEmptyBuffer()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->never())->method('writeRequest');
        $connection->expects($this->never())->method('readResponse');

        $pipeline = new Pipeline(new Client($connection));

        $this->assertSame(array(), $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testExecuteWithFilledBuffer()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->exactly(3))
                   ->method('writeRequest');
        $connection->expects($this->exactly(3))
                   ->method('readResponse')
                   ->will($this->returnCallback($this->getReadCallback()));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->echo('one');
        $pipeline->echo('two');
        $pipeline->echo('three');

        $pipeline->flushPipeline();

        $this->assertSame(array('one', 'two', 'three'), $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testFlushWithFalseArgumentDiscardsBuffer()
    {
        $pipeline = new Pipeline(new Client());

        $pipeline->echo('one');
        $pipeline->echo('two');
        $pipeline->echo('three');

        $pipeline->flushPipeline(false);

        $this->assertSame(array(), $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testFlushHandlesPartialBuffers()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->exactly(4))
                   ->method('writeRequest');
        $connection->expects($this->exactly(4))
                   ->method('readResponse')
                   ->will($this->returnCallback($this->getReadCallback()));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->echo('one');
        $pipeline->echo('two');
        $pipeline->flushPipeline();
        $pipeline->echo('three');
        $pipeline->echo('four');

        $this->assertSame(array('one', 'two', 'three', 'four'), $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testSwitchesToMasterWithReplicationConnection()
    {
        $pong = new Response\Status('PONG');

        $connection = $this->getMock('Predis\Connection\Aggregate\ReplicationInterface');
        $connection->expects($this->once())
                   ->method('switchTo')
                   ->with('master');
        $connection->expects($this->exactly(3))
                   ->method('writeRequest');
        $connection->expects($this->exactly(3))
                   ->method('readResponse')
                   ->will($this->returnValue($pong));

        $pipeline = new Pipeline(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame(array($pong, $pong, $pong), $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testExecuteAcceptsCallableArgument()
    {
        $test = $this;
        $pipeline = new Pipeline(new Client());

        $callable = function ($pipe) use ($test, $pipeline) {
            $test->assertSame($pipeline, $pipe);
            $pipe->flushPipeline(false);
        };

        $pipeline->execute($callable);
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     */
    public function testExecuteDoesNotAcceptNonCallableArgument()
    {
        $noncallable = new \stdClass();

        $pipeline = new Pipeline(new Client());
        $pipeline->execute($noncallable);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     */
    public function testExecuteInsideCallableArgumentThrowsException()
    {
        $pipeline = new Pipeline(new Client());

        $pipeline->execute(function ($pipe) {
            $pipe->execute();
        });
    }

    /**
     * @group disconnected
     */
    public function testExecuteWithCallableArgumentRunsPipelineInCallable()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->exactly(4))
                   ->method('writeRequest');
        $connection->expects($this->exactly(4))
                   ->method('readResponse')
                   ->will($this->returnCallback($this->getReadCallback()));

        $pipeline = new Pipeline(new Client($connection));

        $responses = $pipeline->execute(function ($pipe) {
            $pipe->echo('one');
            $pipe->echo('two');
            $pipe->echo('three');
            $pipe->echo('four');
        });

        $this->assertSame(array('one', 'two', 'three', 'four'), $responses);
    }

    /**
     * @group disconnected
     */
    public function testExecuteWithCallableArgumentHandlesExceptions()
    {
        $exception = null;

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->never())->method('writeRequest');
        $connection->expects($this->never())->method('readResponse');

        $pipeline = new Pipeline(new Client($connection));

        $exception = null;
        $responses = null;

        try {
            $responses = $pipeline->execute(function ($pipe) {
                $pipe->echo('one');
                $pipe->echo('two');
                throw new ClientException('TEST');
            });
        } catch (\Exception $exception) {
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
    public function testIntegrationWithFluentInterface()
    {
        $pipeline = $this->getClient()->pipeline();

        $results = $pipeline->echo('one')
                            ->echo('two')
                            ->echo('three')
                            ->execute();

        $this->assertSame(array('one', 'two', 'three'), $results);
    }

    /**
     * @group connected
     */
    public function testIntegrationWithCallableBlock()
    {
        $client = $this->getClient();

        $results = $client->pipeline(function ($pipe) {
            $pipe->set('foo', 'bar');
            $pipe->get('foo');
        });

        $this->assertEquals(array('OK', 'bar'), $results);
        $this->assertSame(1, $client->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testOutOfBandMessagesInsidePipeline()
    {
        $oob = null;
        $client = $this->getClient();

        $results = $client->pipeline(function ($pipe) use (&$oob) {
            $pipe->set('foo', 'bar');
            $oob = $pipe->getClient()->echo('oob message');
            $pipe->get('foo');
        });

        $this->assertEquals(array('OK', 'bar'), $results);
        $this->assertSame('oob message', $oob);
        $this->assertSame(1, $client->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testIntegrationWithClientExceptionInCallableBlock()
    {
        $exception = null;

        $client = $this->getClient();

        try {
            $client->pipeline(function ($pipe) {
                $pipe->set('foo', 'bar');
                throw new ClientException('TEST');
            });
        } catch (\Exception $exception) {
            // NOOP
        }

        $this->assertInstanceOf('Predis\ClientException', $exception);
        $this->assertSame('TEST', $exception->getMessage());
        $this->assertSame(0, $client->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testIntegrationWithServerExceptionInCallableBlock()
    {
        $exception = null;

        $client = $this->getClient();

        try {
            $client->pipeline(function ($pipe) {
                $pipe->set('foo', 'bar');
                // LPUSH on a string key fails, but won't stop
                // the pipeline to send the commands.
                $pipe->lpush('foo', 'bar');
                $pipe->set('hoge', 'piyo');
            });
        } catch (\Exception $exception) {
            // NOOP
        }

        $this->assertInstanceOf('Predis\Response\ServerException', $exception);
        $this->assertSame(1, $client->exists('foo'));
        $this->assertSame(1, $client->exists('hoge'));
    }

    /**
     * @group connected
     */
    public function testIntegrationWithServerErrorInCallableBlock()
    {
        $client = $this->getClient(array(), array('exceptions' => false));

        $results = $client->pipeline(function ($pipe) {
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
     * Returns a client instance connected to the specified Redis
     * server instance to perform integration tests.
     *
     * @param array $parameters Additional connection parameters.
     * @param array $options    Additional client options.
     *
     * @return Client
     */
    protected function getClient(array $parameters = array(), array $options = array())
    {
        return $this->createClient($parameters, $options);
    }

    /**
     * Helper method that returns a callback used to emulate the response to an
     * ECHO command.
     *
     * @return \Closure
     */
    protected function getReadCallback()
    {
        return function ($command) {
            if (($id = $command->getId()) !== 'ECHO') {
                throw new \InvalidArgumentException("Expected ECHO, got {$id}");
            }

            list($echoed) = $command->getArguments();

            return $echoed;
        };
    }
}
