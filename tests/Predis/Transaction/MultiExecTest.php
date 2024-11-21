<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Transaction;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Client;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response;
use PredisTestCase;
use RuntimeException;

/**
 * @group realm-transaction
 */
class MultiExecTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnUnsupportedMultiExecInCommandFactory(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('MULTI, EXEC and DISCARD are not supported by the current command factory.');

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->once())
            ->method('supports')
            ->with('MULTI', 'EXEC', 'DISCARD')
            ->willReturn(false);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $client = new Client($connection, ['commands' => $commands]);

        new MultiExec($client);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnUnsupportedWatchInCommandFactory(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('WATCH is not supported by the current command factory.');

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->exactly(2))
            ->method('supports')
            ->withConsecutive(
                ['MULTI', 'EXEC', 'DISCARD'],
                ['WATCH']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $client = new Client($connection, ['commands' => $commands]);

        $tx = new MultiExec($client, ['options' => 'cas']);
        $tx->watch('foo');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnUnsupportedUnwatchInCommandFactory(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('UNWATCH is not supported by the current command factory.');

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->exactly(2))
            ->method('supports')
            ->withConsecutive(
                ['MULTI', 'EXEC', 'DISCARD'],
                ['UNWATCH']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $client = new Client($connection, ['commands' => $commands]);

        $tx = new MultiExec($client, ['options' => 'cas']);

        $tx->unwatch('foo');
    }

    /**
     * @group disconnected
     */
    public function testExecutionWithFluentInterface(): void
    {
        $commands = [];
        $expected = ['one', 'two', 'three'];

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $this->assertSame($expected, $tx->echo('one')->echo('two')->echo('three')->execute());
        $this->assertSame(['MULTI', 'ECHO', 'ECHO', 'ECHO', 'EXEC'], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testExecutionWithCallable(): void
    {
        $commands = [];
        $expected = ['one', 'two', 'three'];

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->echo('one');
            $tx->echo('two');
            $tx->echo('three');
        });

        $this->assertSame($expected, $responses);
        $this->assertSame(['MULTI', 'ECHO', 'ECHO', 'ECHO', 'EXEC'], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testCannotMixExecutionWithFluentInterfaceAndCallable(): void
    {
        $commands = [];

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $exception = null;

        try {
            $tx->echo('foo')->execute(function ($tx) {
                $tx->echo('bar');
            });
        } catch (Exception $ex) {
            $exception = $ex;
        }

        $this->assertInstanceOf('Predis\ClientException', $exception);
        $this->assertSame(['MULTI', 'ECHO', 'DISCARD'], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testEmptyTransactionDoesNotSendMultiExecCommands(): void
    {
        $commands = [];

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            // NOOP
        });

        $this->assertNull($responses);
        $this->assertSame([], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnExecInsideTransactionBlock(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('Cannot invoke "execute" or "exec" inside an active transaction context');

        $commands = [];

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->exec();
        });

        $this->assertNull($responses);
        $this->assertSame([], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testEmptyTransactionIgnoresDiscard(): void
    {
        $commands = [];

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->discard();
        });

        $this->assertNull($responses);
        $this->assertSame([], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testTransactionWithCommandsSendsDiscard(): void
    {
        $commands = [];

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->set('foo', 'bar');
            $tx->get('foo');
            $tx->discard();
        });

        $this->assertNull($responses);
        $this->assertSame(['MULTI', 'SET', 'GET', 'DISCARD'], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testSendMultiOnCommandsFollowingDiscard(): void
    {
        $commands = [];
        $expected = ['after DISCARD'];

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->echo('before DISCARD');
            $tx->discard();
            $tx->echo('after DISCARD');
        });

        $this->assertSame($responses, $expected);
        $this->assertSame(['MULTI', 'ECHO', 'DISCARD', 'MULTI', 'ECHO', 'EXEC'], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnWatchInsideMulti(): void
    {
        $this->expectException('Predis\ClientException');

        $callback = $this->getExecuteCallback();
        $tx = $this->getMockedTransaction($callback);

        $tx->echo('foobar')->watch('foo')->execute();
    }

    /**
     * @group disconnected
     */
    public function testUnwatchInsideMulti(): void
    {
        $commands = [];
        $expected = ['foobar', true];

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->echo('foobar')->unwatch('foo')->execute();

        $this->assertSame($responses, $expected);
        $this->assertSame(['MULTI', 'ECHO', 'UNWATCH', 'EXEC'], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testAutomaticWatchInOptions(): void
    {
        $txCommands = $casCommands = [];
        $expected = ['bar', 'piyo'];
        $options = ['watch' => ['foo', 'hoge']];

        $callback = $this->getExecuteCallback($expected, $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $responses = $tx->execute(function ($tx) {
            $tx->get('foo');
            $tx->get('hoge');
        });

        $this->assertSame($responses, $expected);
        $this->assertSame(['WATCH'], self::commandsToIDs($casCommands));
        $this->assertSame(['foo', 'hoge'], $casCommands[0]->getArguments());
        $this->assertSame(['MULTI', 'GET', 'GET', 'EXEC'], self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testCheckAndSetWithFluentInterface(): void
    {
        $txCommands = $casCommands = [];
        $expected = ['bar', 'piyo'];
        $options = ['cas' => true, 'watch' => ['foo', 'hoge']];

        $callback = $this->getExecuteCallback($expected, $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $tx->watch('foobar');
        $this->assertSame('DUMMY_RESPONSE', $tx->get('foo'));
        $this->assertSame('DUMMY_RESPONSE', $tx->get('hoge'));

        $responses = $tx
            ->multi()
            ->get('foo')
            ->get('hoge')
            ->execute();

        $this->assertSame($responses, $expected);
        $this->assertSame(['WATCH', 'WATCH', 'GET', 'GET'], self::commandsToIDs($casCommands));
        $this->assertSame(['MULTI', 'GET', 'GET', 'EXEC'], self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testCheckAndSetWithBlock(): void
    {
        $txCommands = $casCommands = [];
        $expected = ['bar', 'piyo'];
        $options = ['cas' => true, 'watch' => ['foo', 'hoge']];

        $callback = $this->getExecuteCallback($expected, $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $test = $this;
        $responses = $tx->execute(function ($tx) use ($test) {
            $tx->watch('foobar');

            $response1 = $tx->get('foo');
            $response2 = $tx->get('hoge');

            $test->assertSame('DUMMY_RESPONSE', $response1);
            $test->assertSame('DUMMY_RESPONSE', $response2);

            $tx->multi();

            $tx->get('foo');
            $tx->get('hoge');
        });

        $this->assertSame($responses, $expected);
        $this->assertSame(['WATCH', 'WATCH', 'GET', 'GET'], self::commandsToIDs($casCommands));
        $this->assertSame(['MULTI', 'GET', 'GET', 'EXEC'], self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testCheckAndSetWithEmptyBlock(): void
    {
        $txCommands = $casCommands = [];
        $options = ['cas' => true];

        $callback = $this->getExecuteCallback([], $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $tx->execute(function ($tx) {
            $tx->multi();
        });

        $this->assertSame([], self::commandsToIDs($casCommands));
        $this->assertSame([], self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testCheckAndSetWithoutExec(): void
    {
        $txCommands = $casCommands = [];
        $options = ['cas' => true];

        $callback = $this->getExecuteCallback([], $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $tx->execute(function ($tx) {
            $tx->get('foo');
            $tx->set('hoge', 'piyo');
        });

        $this->assertSame(['GET', 'SET'], self::commandsToIDs($casCommands));
        $this->assertSame([], self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnAutomaticRetriesWithFluentInterface(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('Automatic retries are supported only when a callable block is provided');

        $options = ['retry' => 1];

        $callback = $this->getExecuteCallback();
        $tx = $this->getMockedTransaction($callback, $options);

        $tx->echo('message')->execute();
    }

    /**
     * @group disconnected
     */
    public function testAutomaticRetryOnServerSideTransactionAbort(): void
    {
        $casCommands = $txCommands = [];
        $expected = ['bar'];
        $options = ['watch' => ['foo', 'bar'], 'retry' => ($attempts = 2) + 1];

        $signal = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $signal
            ->expects($this->exactly($attempts))
            ->method('__invoke');

        $callback = $this->getExecuteCallback($expected, $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $responses = $tx->execute(function (MultiExec $tx) use ($signal, &$attempts) {
            $tx->get('foo');

            if ($attempts > 0) {
                $attempts--;
                $signal();

                $tx->echo('!!ABORT!!');
            }
        });

        $this->assertSame($responses, $expected);
        $this->assertSame(['WATCH'], self::commandsToIDs($casCommands));
        $this->assertSame(['foo', 'bar'], $casCommands[0]->getArguments());
        $this->assertSame(['MULTI', 'GET', 'EXEC'], self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnServerSideTransactionAbort(): void
    {
        $this->expectException('Predis\Transaction\AbortedMultiExecException');

        $callback = $this->getExecuteCallback();
        $tx = $this->getMockedTransaction($callback);

        $tx->execute(function ($tx) {
            $tx->echo('!!ABORT!!');
        });
    }

    /**
     * @group disconnected
     */
    public function testHandlesStandardExceptionsInBlock(): void
    {
        $commands = [];
        $expected = ['foobar', true];

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = null;

        try {
            $responses = $tx->execute(function (MultiExec $tx) {
                $tx->set('foo', 'bar');
                $tx->get('foo');

                throw new RuntimeException('TEST');
            });
        } catch (Exception $ex) {
            // NOOP
        }

        $this->assertNull($responses);
        $this->assertIsArray($expected);
        $this->assertSame(['MULTI', 'SET', 'GET', 'DISCARD'], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testHandlesServerExceptionsInBlock(): void
    {
        $commands = [];
        $expected = ['foobar', true];

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = null;

        try {
            $responses = $tx->execute(function (MultiExec $tx) {
                $tx->set('foo', 'bar');
                $tx->echo('ERR Invalid operation');
                $tx->get('foo');
            });
        } catch (Response\ServerException $ex) {
            $tx->discard();
        }

        $this->assertNull($responses);
        $this->assertSame(['MULTI', 'SET', 'ECHO', 'DISCARD'], self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testProperlyDiscardsTransactionAfterServerExceptionInBlock(): void
    {
        $connection = $this->getMockedConnection(function (CommandInterface $command) {
            switch ($command->getId()) {
                case 'MULTI':
                    return true;

                case 'ECHO':
                    return new Response\Error('ERR simulated failure on ECHO');

                case 'EXEC':
                    return new Response\Error('EXECABORT Transaction discarded because of previous errors.');

                default:
                    return new Response\Status('QUEUED');
            }
        });

        $client = new Client($connection);

        // First attempt
        $tx = new MultiExec($client);

        try {
            $tx->multi()->set('foo', 'bar')->echo('simulated failure')->exec();
        } catch (Exception $exception) {
            $this->assertInstanceOf('Predis\Transaction\AbortedMultiExecException', $exception);
            $this->assertSame('ERR simulated failure on ECHO', $exception->getMessage());
        }

        // Second attempt
        $tx = new MultiExec($client);

        try {
            $tx->multi()->set('foo', 'bar')->echo('simulated failure')->exec();
        } catch (Exception $exception) {
            $this->assertInstanceOf('Predis\Transaction\AbortedMultiExecException', $exception);
            $this->assertSame('ERR simulated failure on ECHO', $exception->getMessage());
        }
    }

    /**
     * @group disconnected
     */
    public function testExceptionsOptionTakesPrecedenceOverClientOptionsWhenFalse(): void
    {
        $expected = ['before', new Response\Error('ERR simulated error'), 'after'];

        $connection = $this->getMockedConnection(function (CommandInterface $command) use ($expected) {
            switch ($command->getId()) {
                case 'MULTI':
                    return true;

                case 'EXEC':
                    return $expected;

                default:
                    return new Response\Status('QUEUED');
            }
        });

        $client = new Client($connection, ['exceptions' => true]);
        $tx = new MultiExec($client, ['exceptions' => false]);

        $result = $tx
            ->multi()
            ->echo('before')
            ->echo('ERROR PLEASE!')
            ->echo('after')
            ->exec();

        $this->assertSame($expected, $result);
    }

    /**
     * @group disconnected
     */
    public function testExceptionsOptionTakesPrecedenceOverClientOptionsWhenTrue(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR simulated error');

        $expected = ['before', new Response\Error('ERR simulated error'), 'after'];

        $connection = $this->getMockedConnection(function (CommandInterface $command) use ($expected) {
            switch ($command->getId()) {
                case 'MULTI':
                    return true;

                case 'EXEC':
                    return $expected;

                default:
                    return new Response\Status('QUEUED');
            }
        });

        $client = new Client($connection, ['exceptions' => false]);
        $tx = new MultiExec($client, ['exceptions' => true]);

        $tx->multi()->echo('before')->echo('ERROR PLEASE!')->echo('after')->exec();
    }

    /**
     * @group disconnected
     */
    public function testExceptionsOptionDoesNotAffectTransactionControlCommands(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR simulated failure on EXEC');

        $connection = $this->getMockedConnection(function (CommandInterface $command) {
            switch ($command->getId()) {
                case 'MULTI':
                    return true;

                case 'EXEC':
                    return new Response\Error('ERR simulated failure on EXEC');

                default:
                    return new Response\Status('QUEUED');
            }
        });

        $client = new Client($connection, ['exceptions' => false]);
        $tx = new MultiExec($client);

        $tx->multi()->echo('test')->exec();
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testIntegrationHandlesStandardExceptionsInBlock(): void
    {
        $client = $this->getClient();
        $exception = null;

        try {
            $client->transaction(function (MultiExec $tx) {
                $tx->set('foo', 'bar');
                throw new RuntimeException('TEST');
            });
        } catch (Exception $ex) {
            $exception = $ex;
        }

        $this->assertInstanceOf('RuntimeException', $exception);
        $this->assertSame(0, $client->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testIntegrationThrowsExceptionOnRedisErrorInBlock(): void
    {
        $client = $this->getClient();
        $exception = null;
        $value = (string) rand();

        try {
            $client->transaction(function (MultiExec $tx) use ($value) {
                $tx->set('foo', 'bar');
                $tx->lpush('foo', 'bar');
                $tx->set('foo', $value);
            });
        } catch (Response\ServerException $ex) {
            $exception = $ex;
        }

        $this->assertInstanceOf('Predis\Response\ErrorInterface', $exception);
        $this->assertSame($value, $client->get('foo'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     */
    public function testIntegrationReturnsErrorObjectOnRedisErrorInBlock(): void
    {
        $client = $this->getClient([], ['exceptions' => false]);

        $responses = $client->transaction(function (MultiExec $tx) {
            $tx->set('foo', 'bar');
            $tx->lpush('foo', 'bar');
            $tx->echo('foobar');
        });

        $this->assertInstanceOf('Predis\Response\Status', $responses[0]);
        $this->assertInstanceOf('Predis\Response\Error', $responses[1]);
        $this->assertSame('foobar', $responses[2]);
    }

    /**
     * @group connected
     * @group ext-relay
     */
    public function testIntegrationReturnsErrorObjectOnRedisErrorInBlockWhenUsingRelay(): void
    {
        $client = $this->getClient([], ['exceptions' => false]);

        $responses = $client->transaction(function (MultiExec $tx) {
            $tx->set('foo', 'bar');
            $tx->lpush('foo', 'bar');
            $tx->echo('foobar');
        });

        $this->assertSame('OK', $responses[0]);
        $this->assertInstanceOf('Predis\Response\Error', $responses[1]);
        $this->assertSame('foobar', $responses[2]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testIntegrationSendMultiOnCommandsAfterDiscard(): void
    {
        $client = $this->getClient();

        $responses = $client->transaction(function (MultiExec $tx) {
            $tx->set('foo', 'bar');
            $tx->discard();
            $tx->set('hoge', 'piyo');
        });

        $this->assertCount(1, $responses);
        $this->assertSame(0, $client->exists('foo'));
        $this->assertSame(1, $client->exists('hoge'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testIntegrationWritesOnWatchedKeysAbortTransaction(): void
    {
        $exception = null;
        $client1 = $this->getClient();
        $client2 = $this->getClient();

        try {
            $client1->transaction(['watch' => 'sentinel'], function ($tx) use ($client2) {
                $tx->set('sentinel', 'client1');
                $tx->get('sentinel');
                $client2->set('sentinel', 'client2');
            });
        } catch (AbortedMultiExecException $ex) {
            $exception = $ex;
        }

        $this->assertInstanceOf('Predis\Transaction\AbortedMultiExecException', $exception);
        $this->assertSame('client2', $client1->get('sentinel'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testIntegrationCheckAndSetWithDiscardAndRetry(): void
    {
        $client = $this->getClient();

        $client->set('foo', 'bar');
        $options = ['watch' => 'foo', 'cas' => true];

        $responses = $client->transaction($options, function ($tx) {
            $tx->watch('foobar');
            $foo = $tx->get('foo');

            $tx->multi();
            $tx->set('foobar', $foo);
            $tx->discard();
            $tx->mget('foo', 'foobar');
        });

        $this->assertIsArray($responses);
        $this->assertSame([['bar', null]], $responses);

        $hijack = true;
        $client2 = $this->getClient();
        $client->set('foo', 'bar');

        $options = ['watch' => 'foo', 'cas' => true, 'retry' => 1];
        $responses = $client->transaction($options, function ($tx) use ($client2, &$hijack) {
            $foo = $tx->get('foo');
            $tx->multi();

            $tx->set('foobar', $foo);
            $tx->discard();

            if ($hijack) {
                $hijack = false;
                $client2->set('foo', 'hijacked!');
            }

            $tx->mget('foo', 'foobar');
        });

        $this->assertIsArray($responses);
        $this->assertSame([['hijacked!', null]], $responses);
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a mocked instance of Predis\Connection\NodeConnectionInterface
     * using the specified callback to return values from executeCommand().
     *
     * @param callable $executeCallback
     *
     * @return NodeConnectionInterface|MockObject
     */
    protected function getMockedConnection(callable $executeCallback)
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->any())
            ->method('executeCommand')
            ->willReturnCallback($executeCallback);

        return $connection;
    }

    /**
     * Returns a mocked instance of Predis\Transaction\MultiExec using
     * the specified callback to return values from the executeCommand method
     * of the underlying connection.
     *
     * @param callable $executeCallback
     * @param array    $txOpts
     * @param array    $clientOpts
     *
     * @return MultiExec
     */
    protected function getMockedTransaction($executeCallback, $txOpts = null, $clientOpts = null): MultiExec
    {
        $connection = $this->getMockedConnection($executeCallback);
        $client = new Client($connection, $clientOpts ?: []);
        $transaction = new MultiExec($client, $txOpts ?: []);

        return $transaction;
    }

    /**
     * Returns a callback emulating a server-side MULTI/EXEC context.
     *
     * @param ?array $expected List of expected responses
     * @param ?array $commands Reference to an array storing the whole flow of commands
     * @param ?array $cas      Reference to an array storing CAS operations performed by the transaction
     *
     * @return callable
     */
    protected function getExecuteCallback(
        ?array $expected = [],
        ?array &$commands = [],
        ?array &$cas = []
    ): callable {
        $multi = $watch = $abort = false;

        return function (CommandInterface $command) use (&$expected, &$commands, &$cas, &$multi, &$watch, &$abort) {
            $cmd = $command->getId();

            if ($multi || $cmd === 'MULTI') {
                $commands[] = $command;
            } else {
                $cas[] = $command;
            }

            switch ($cmd) {
                case 'WATCH':
                    if ($multi) {
                        return new Response\Error("ERR $cmd inside MULTI is not allowed");
                    }

                    return $watch = true;

                case 'MULTI':
                    if ($multi) {
                        return new Response\Error('ERR MULTI calls can not be nested');
                    }

                    return $multi = true;

                case 'EXEC':
                    if (!$multi) {
                        return new Response\Error("ERR $cmd without MULTI");
                    }

                    $watch = $multi = false;

                    if ($abort) {
                        $commands = $cas = [];
                        $abort = false;

                        return;
                    }

                    return $expected;

                case 'DISCARD':
                    if (!$multi) {
                        return new Response\Error("ERR $cmd without MULTI");
                    }

                    $watch = $multi = false;

                    return true;

                case 'ECHO':
                    @[$trigger] = $command->getArguments();
                    if (strpos($trigger, 'ERR ') === 0) {
                        throw new Response\ServerException($trigger);
                    }

                    if ($trigger === '!!ABORT!!' && $multi) {
                        $abort = true;
                    }

                    return new Response\Status('QUEUED');

                case 'UNWATCH':
                    $watch = false;
                    // no break

                default:
                    return $multi ? new Response\Status('QUEUED') : 'DUMMY_RESPONSE';
            }
        };
    }

    /**
     * Converts an array of command instances to an array of command IDs.
     *
     * @param CommandInterface[] $commands List of commands instances
     *
     * @return array
     */
    protected static function commandsToIDs(array $commands): array
    {
        return array_map(function ($cmd) { return $cmd->getId(); }, $commands);
    }

    /**
     * Returns a client instance connected to the specified Redis
     * server instance to perform integration tests.
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
