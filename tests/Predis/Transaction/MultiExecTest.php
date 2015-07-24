<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Transaction;

use Predis\Client;
use Predis\Command\CommandInterface;
use Predis\Response;
use PredisTestCase;

/**
 * @group realm-transaction
 */
class MultiExecTest extends PredisTestCase
{
    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The current profile does not support MULTI, EXEC and DISCARD.
     */
    public function testThrowsExceptionOnUnsupportedMultiExecInProfile()
    {
        $profile = $this->getMock('Predis\Profile\ProfileInterface');
        $profile->expects($this->once())
                ->method('supportsCommands')
                ->with(array('MULTI', 'EXEC', 'DISCARD'))
                ->will($this->returnValue(false));

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $client = new Client($connection, array('profile' => $profile));

        new MultiExec($client);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage WATCH is not supported by the current profile.
     */
    public function testThrowsExceptionOnUnsupportedWatchInProfile()
    {
        $profile = $this->getMock('Predis\Profile\ProfileInterface');
        $profile->expects($this->once())
                ->method('supportsCommands')
                ->with(array('MULTI', 'EXEC', 'DISCARD'))
                ->will($this->returnValue(true));
        $profile->expects($this->once())
                ->method('supportsCommand')
                ->with('WATCH')
                ->will($this->returnValue(false));

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $client = new Client($connection, array('profile' => $profile));

        $tx = new MultiExec($client, array('options' => 'cas'));
        $tx->watch('foo');
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage UNWATCH is not supported by the current profile.
     */
    public function testThrowsExceptionOnUnsupportedUnwatchInProfile()
    {
        $profile = $this->getMock('Predis\Profile\ProfileInterface');
        $profile->expects($this->once())
                ->method('supportsCommands')
                ->with(array('MULTI', 'EXEC', 'DISCARD'))
                ->will($this->returnValue(true));
        $profile->expects($this->once())
                ->method('supportsCommand')
                ->with('UNWATCH')
                ->will($this->returnValue(false));

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $client = new Client($connection, array('profile' => $profile));

        $tx = new MultiExec($client, array('options' => 'cas'));

        $tx->unwatch('foo');
    }

    /**
     * @group disconnected
     */
    public function testExecutionWithFluentInterface()
    {
        $commands = array();
        $expected = array('one', 'two', 'three');

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $this->assertSame($expected, $tx->echo('one')->echo('two')->echo('three')->execute());
        $this->assertSame(array('MULTI', 'ECHO', 'ECHO', 'ECHO', 'EXEC'), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testExecutionWithCallable()
    {
        $commands = array();
        $expected = array('one', 'two', 'three');

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->echo('one');
            $tx->echo('two');
            $tx->echo('three');
        });

        $this->assertSame($expected, $responses);
        $this->assertSame(array('MULTI', 'ECHO', 'ECHO', 'ECHO', 'EXEC'), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testCannotMixExecutionWithFluentInterfaceAndCallable()
    {
        $commands = array();

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $exception = null;

        try {
            $tx->echo('foo')->execute(function ($tx) {
                $tx->echo('bar');
            });
        } catch (\Exception $ex) {
            $exception = $ex;
        }

        $this->assertInstanceOf('Predis\ClientException', $exception);
        $this->assertSame(array('MULTI', 'ECHO', 'DISCARD'), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testEmptyTransactionDoesNotSendMultiExecCommands()
    {
        $commands = array();

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            // NOOP
        });

        $this->assertNull($responses);
        $this->assertSame(array(), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     * @expectedExceptionMessage Cannot invoke "execute" or "exec" inside an active transaction context.
     */
    public function testThrowsExceptionOnExecInsideTransactionBlock()
    {
        $commands = array();

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->exec();
        });

        $this->assertNull($responses);
        $this->assertSame(array(), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testEmptyTransactionIgnoresDiscard()
    {
        $commands = array();

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->discard();
        });

        $this->assertNull($responses);
        $this->assertSame(array(), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testTransactionWithCommandsSendsDiscard()
    {
        $commands = array();

        $callback = $this->getExecuteCallback(null, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->set('foo', 'bar');
            $tx->get('foo');
            $tx->discard();
        });

        $this->assertNull($responses);
        $this->assertSame(array('MULTI', 'SET', 'GET', 'DISCARD'), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testSendMultiOnCommandsFollowingDiscard()
    {
        $commands = array();
        $expected = array('after DISCARD');

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->execute(function ($tx) {
            $tx->echo('before DISCARD');
            $tx->discard();
            $tx->echo('after DISCARD');
        });

        $this->assertSame($responses, $expected);
        $this->assertSame(array('MULTI', 'ECHO', 'DISCARD', 'MULTI', 'ECHO', 'EXEC'), self::commandsToIDs($commands));
    }
    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     */
    public function testThrowsExceptionOnWatchInsideMulti()
    {
        $callback = $this->getExecuteCallback();
        $tx = $this->getMockedTransaction($callback);

        $tx->echo('foobar')->watch('foo')->execute();
    }

    /**
     * @group disconnected
     */
    public function testUnwatchInsideMulti()
    {
        $commands = array();
        $expected = array('foobar', true);

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = $tx->echo('foobar')->unwatch('foo')->execute();

        $this->assertSame($responses, $expected);
        $this->assertSame(array('MULTI', 'ECHO', 'UNWATCH', 'EXEC'), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testAutomaticWatchInOptions()
    {
        $txCommands = $casCommands = array();
        $expected = array('bar', 'piyo');
        $options = array('watch' => array('foo', 'hoge'));

        $callback = $this->getExecuteCallback($expected, $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $responses = $tx->execute(function ($tx) {
            $tx->get('foo');
            $tx->get('hoge');
        });

        $this->assertSame($responses, $expected);
        $this->assertSame(array('WATCH'), self::commandsToIDs($casCommands));
        $this->assertSame(array('foo', 'hoge'), $casCommands[0]->getArguments());
        $this->assertSame(array('MULTI', 'GET', 'GET', 'EXEC'), self::commandsToIDs($txCommands));
    }
    /**
     * @group disconnected
     */
    public function testCheckAndSetWithFluentInterface()
    {
        $txCommands = $casCommands = array();
        $expected = array('bar', 'piyo');
        $options = array('cas' => true, 'watch' => array('foo', 'hoge'));

        $callback = $this->getExecuteCallback($expected, $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $tx->watch('foobar');
        $this->assertSame('DUMMY_RESPONSE', $tx->get('foo'));
        $this->assertSame('DUMMY_RESPONSE', $tx->get('hoge'));

        $responses = $tx->multi()
                        ->get('foo')
                        ->get('hoge')
                        ->execute();

        $this->assertSame($responses, $expected);
        $this->assertSame(array('WATCH', 'WATCH', 'GET', 'GET'), self::commandsToIDs($casCommands));
        $this->assertSame(array('MULTI', 'GET', 'GET', 'EXEC'), self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testCheckAndSetWithBlock()
    {
        $txCommands = $casCommands = array();
        $expected = array('bar', 'piyo');
        $options = array('cas' => true, 'watch' => array('foo', 'hoge'));

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
        $this->assertSame(array('WATCH', 'WATCH', 'GET', 'GET'), self::commandsToIDs($casCommands));
        $this->assertSame(array('MULTI', 'GET', 'GET', 'EXEC'), self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testCheckAndSetWithEmptyBlock()
    {
        $txCommands = $casCommands = array();
        $options = array('cas' => true);

        $callback = $this->getExecuteCallback(array(), $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $tx->execute(function ($tx) {
            $tx->multi();
        });

        $this->assertSame(array(), self::commandsToIDs($casCommands));
        $this->assertSame(array(), self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     */
    public function testCheckAndSetWithoutExec()
    {
        $txCommands = $casCommands = array();
        $options = array('cas' => true);

        $callback = $this->getExecuteCallback(array(), $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $tx->execute(function ($tx) {
            $tx->get('foo');
            $tx->set('hoge', 'piyo');
        });

        $this->assertSame(array('GET', 'SET'), self::commandsToIDs($casCommands));
        $this->assertSame(array(), self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     * @expectedExceptionMessage Automatic retries are supported only when a callable block is provided.
     */
    public function testThrowsExceptionOnAutomaticRetriesWithFluentInterface()
    {
        $options = array('retry' => 1);

        $callback = $this->getExecuteCallback();
        $tx = $this->getMockedTransaction($callback, $options);

        $tx->echo('message')->execute();
    }

    /**
     * @group disconnected
     */
    public function testAutomaticRetryOnServerSideTransactionAbort()
    {
        $casCommands = $txCommands = array();
        $expected = array('bar');
        $options = array('watch' => array('foo', 'bar'), 'retry' => ($attempts = 2) + 1);

        $sentinel = $this->getMock('stdClass', array('signal'));
        $sentinel->expects($this->exactly($attempts))->method('signal');

        $callback = $this->getExecuteCallback($expected, $txCommands, $casCommands);
        $tx = $this->getMockedTransaction($callback, $options);

        $responses = $tx->execute(function ($tx) use ($sentinel, &$attempts) {
            $tx->get('foo');

            if ($attempts > 0) {
                $attempts -= 1;
                $sentinel->signal();

                $tx->echo('!!ABORT!!');
            }
        });

        $this->assertSame($responses, $expected);
        $this->assertSame(array('WATCH'), self::commandsToIDs($casCommands));
        $this->assertSame(array('foo', 'bar'), $casCommands[0]->getArguments());
        $this->assertSame(array('MULTI', 'GET', 'EXEC'), self::commandsToIDs($txCommands));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Transaction\AbortedMultiExecException
     */
    public function testThrowsExceptionOnServerSideTransactionAbort()
    {
        $callback = $this->getExecuteCallback();
        $tx = $this->getMockedTransaction($callback);

        $tx->execute(function ($tx) {
            $tx->echo('!!ABORT!!');
        });
    }

    /**
     * @group disconnected
     */
    public function testHandlesStandardExceptionsInBlock()
    {
        $commands = array();
        $expected = array('foobar', true);

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = null;

        try {
            $responses = $tx->execute(function ($tx) {
                $tx->set('foo', 'bar');
                $tx->get('foo');

                throw new \RuntimeException('TEST');
            });
        } catch (\Exception $ex) {
            // NOOP
        }

        $this->assertNull($responses, $expected);
        $this->assertSame(array('MULTI', 'SET', 'GET', 'DISCARD'), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testHandlesServerExceptionsInBlock()
    {
        $commands = array();
        $expected = array('foobar', true);

        $callback = $this->getExecuteCallback($expected, $commands);
        $tx = $this->getMockedTransaction($callback);

        $responses = null;

        try {
            $responses = $tx->execute(function ($tx) {
                $tx->set('foo', 'bar');
                $tx->echo('ERR Invalid operation');
                $tx->get('foo');
            });
        } catch (Response\ServerException $ex) {
            $tx->discard();
        }

        $this->assertNull($responses);
        $this->assertSame(array('MULTI', 'SET', 'ECHO', 'DISCARD'), self::commandsToIDs($commands));
    }

    /**
     * @group disconnected
     */
    public function testProperlyDiscardsTransactionAfterServerExceptionInBlock()
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
        } catch (\Exception $exception) {
            $this->assertInstanceOf('Predis\Transaction\AbortedMultiExecException', $exception);
            $this->assertSame('ERR simulated failure on ECHO', $exception->getMessage());
        }

        // Second attempt
        $tx = new MultiExec($client);

        try {
            $tx->multi()->set('foo', 'bar')->echo('simulated failure')->exec();
        } catch (\Exception $exception) {
            $this->assertInstanceOf('Predis\Transaction\AbortedMultiExecException', $exception);
            $this->assertSame('ERR simulated failure on ECHO', $exception->getMessage());
        }
    }

    /**
     * @group disconnected
     */
    public function testExceptionsOptionTakesPrecedenceOverClientOptionsWhenFalse()
    {
        $expected = array('before', new Response\Error('ERR simulated error'), 'after');

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

        $client = new Client($connection, array('exceptions' => true));
        $tx = new MultiExec($client, array('exceptions' => false));

        $result = $tx->multi()
                     ->echo('before')
                     ->echo('ERROR PLEASE!')
                     ->echo('after')
                     ->exec();

        $this->assertSame($expected, $result);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR simulated error
     */
    public function testExceptionsOptionTakesPrecedenceOverClientOptionsWhenTrue()
    {
        $expected = array('before', new Response\Error('ERR simulated error'), 'after');

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

        $client = new Client($connection, array('exceptions' => false));
        $tx = new MultiExec($client, array('exceptions' => true));

        $tx->multi()->echo('before')->echo('ERROR PLEASE!')->echo('after')->exec();
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR simulated failure on EXEC
     */
    public function testExceptionsOptionDoesNotAffectTransactionControlCommands()
    {
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

        $client = new Client($connection, array('exceptions' => false));
        $tx = new MultiExec($client);

        $tx->multi()->echo('test')->exec();
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testIntegrationHandlesStandardExceptionsInBlock()
    {
        $client = $this->getClient();
        $exception = null;

        try {
            $client->transaction(function ($tx) {
                $tx->set('foo', 'bar');
                throw new \RuntimeException('TEST');
            });
        } catch (\Exception $ex) {
            $exception = $ex;
        }

        $this->assertInstanceOf('RuntimeException', $exception);
        $this->assertSame(0, $client->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testIntegrationThrowsExceptionOnRedisErrorInBlock()
    {
        $client = $this->getClient();
        $exception = null;
        $value = (string) rand();

        try {
            $client->transaction(function ($tx) use ($value) {
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
     */
    public function testIntegrationReturnsErrorObjectOnRedisErrorInBlock()
    {
        $client = $this->getClient(array(), array('exceptions' => false));

        $responses = $client->transaction(function ($tx) {
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
     */
    public function testIntegrationSendMultiOnCommandsAfterDiscard()
    {
        $client = $this->getClient();

        $responses = $client->transaction(function ($tx) {
            $tx->set('foo', 'bar');
            $tx->discard();
            $tx->set('hoge', 'piyo');
        });

        $this->assertSame(1, count($responses));
        $this->assertSame(0, $client->exists('foo'));
        $this->assertSame(1, $client->exists('hoge'));
    }

    /**
     * @group connected
     */
    public function testIntegrationWritesOnWatchedKeysAbortTransaction()
    {
        $exception = null;
        $client1 = $this->getClient();
        $client2 = $this->getClient();

        try {
            $client1->transaction(array('watch' => 'sentinel'), function ($tx) use ($client2) {
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
     */
    public function testIntegrationCheckAndSetWithDiscardAndRetry()
    {
        $client = $this->getClient();

        $client->set('foo', 'bar');
        $options = array('watch' => 'foo', 'cas' => true);

        $responses = $client->transaction($options, function ($tx) {
            $tx->watch('foobar');
            $foo = $tx->get('foo');

            $tx->multi();
            $tx->set('foobar', $foo);
            $tx->discard();
            $tx->mget('foo', 'foobar');
        });

        $this->assertInternalType('array', $responses);
        $this->assertSame(array(array('bar', null)), $responses);

        $hijack = true;
        $client2 = $this->getClient();
        $client->set('foo', 'bar');

        $options = array('watch' => 'foo', 'cas' => true, 'retry' => 1);
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

        $this->assertInternalType('array', $responses);
        $this->assertSame(array(array('hijacked!', null)), $responses);
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a mocked instance of Predis\Connection\NodeConnectionInterface
     * using the specified callback to return values from executeCommand().
     *
     * @param \Closure $executeCallback
     *
     * @return \Predis\Connection\NodeConnectionInterface
     */
    protected function getMockedConnection($executeCallback)
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->any())
                   ->method('executeCommand')
                   ->will($this->returnCallback($executeCallback));

        return $connection;
    }

    /**
     * Returns a mocked instance of Predis\Transaction\MultiExec using
     * the specified callback to return values from the executeCommand method
     * of the underlying connection.
     *
     * @param \Closure $executeCallback
     * @param array    $txOpts
     * @param array    $clientOpts
     *
     * @return MultiExec
     */
    protected function getMockedTransaction($executeCallback, $txOpts = null, $clientOpts = null)
    {
        $connection = $this->getMockedConnection($executeCallback);
        $client = new Client($connection, $clientOpts ?: array());
        $transaction = new MultiExec($client, $txOpts ?: array());

        return $transaction;
    }

    /**
     * Returns a callback that emulates a server-side MULTI/EXEC transaction context.
     *
     * @param array $expected Expected responses.
     * @param array $commands Reference to an array storing the whole flow of commands.
     * @param array $cas      Check and set operations performed by the transaction.
     *
     * @return \Closure
     */
    protected function getExecuteCallback($expected = array(), &$commands = array(), &$cas = array())
    {
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
                        $commands = $cas = array();
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
                    @list($trigger) = $command->getArguments();
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
     * Converts an array of instances of Predis\Command\CommandInterface and
     * returns an array containing their IDs.
     *
     * @param array $commands List of commands instances.
     *
     * @return array
     */
    protected static function commandsToIDs($commands)
    {
        return array_map(function ($cmd) { return $cmd->getId(); }, $commands);
    }

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
}
