<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\PubSub;

use PredisTestCase;
use Predis\Client;

/**
 * @group realm-pubsub
 */
class DispatcherLoopTest extends PredisTestCase
{
    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    // NOTE: the following 2 tests fail at random without any apparent reason
    // when executed on our CI environments and these failures are not tied
    // to a particular version of PHP or Redis. It is most likely some weird
    // timing issue on busy systems as it is really rare to get it triggered
    // locally. The chances it is a bug in the library are pretty low so for
    // now we just mark this test skipped on our CI environments (but still
    // enabled for local test runs) and "debug" this issue using a separate
    // branch to avoid having spurious failures on main development branches
    // which is utterly annoying.

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testDispatcherLoopAgainstRedisServer(): void
    {
        $this->markTestSkippedOnCIEnvironment(
            'Test temporarily skipped on CI environments, see note in the body of the test' // TODO
        );

        $parameters = array(
            'host' => constant('REDIS_SERVER_HOST'),
            'port' => constant('REDIS_SERVER_PORT'),
            'database' => constant('REDIS_SERVER_DBNUM'),
            // Prevents suite from hanging on broken test
            'read_write_timeout' => 2,
        );

        $producer = new Client($parameters);
        $producer->connect();

        $consumer = new Client($parameters);
        $consumer->connect();

        $pubsub = new Consumer($consumer);
        $dispatcher = new DispatcherLoop($pubsub);

        $function01 = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $function01
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->with($this->logicalOr(
                $this->equalTo('01:argument'),
                $this->equalTo('01:quit')
            ), $dispatcher)
            ->willReturnCallback(function ($arg, $dispatcher) {
                if ($arg === '01:quit') {
                    $dispatcher->stop();
                }
            });

        $function02 = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $function02
            ->expects($this->once())
            ->method('__invoke')
            ->with('02:argument');

        $function03 = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $function03
            ->expects($this->never())
            ->method('__invoke');

        $dispatcher->attachCallback('function:01', $function01);
        $dispatcher->attachCallback('function:02', $function02);
        $dispatcher->attachCallback('function:03', $function03);

        $producer->publish('function:01', '01:argument');
        $producer->publish('function:02', '02:argument');
        $producer->publish('function:01', '01:quit');

        $dispatcher->run();

        $this->assertEquals('PONG', $consumer->ping());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testDispatcherLoopAgainstRedisServerWithPrefix(): void
    {
        $this->markTestSkippedOnCIEnvironment(
            'Test temporarily skipped on CI environments, see note in the body of the test' // TODO
        );

        $parameters = array(
            'host' => constant('REDIS_SERVER_HOST'),
            'port' => constant('REDIS_SERVER_PORT'),
            'database' => constant('REDIS_SERVER_DBNUM'),
            // Prevents suite from handing on broken test
            'read_write_timeout' => 2,
        );

        $producerNonPfx = new Client($parameters);
        $producerNonPfx->connect();

        $producerPfx = new Client($parameters, array('prefix' => 'foobar'));
        $producerPfx->connect();

        $consumer = new Client($parameters, array('prefix' => 'foobar'));

        $pubsub = new Consumer($consumer);
        $dispatcher = new DispatcherLoop($pubsub);

        $callback = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callback
            ->expects($this->exactly(1))
            ->method('__invoke')
            ->with($this->equalTo('arg:prefixed'), $dispatcher)
            ->willReturnCallback(function ($arg, $dispatcher) {
                $dispatcher->stop();
            });

        $dispatcher->attachCallback('callback', $callback);

        $producerNonPfx->publish('callback', 'arg:non-prefixed');
        $producerPfx->publish('callback', 'arg:prefixed');

        $dispatcher->run();

        $this->assertEquals('PONG', $consumer->ping());
    }
}
