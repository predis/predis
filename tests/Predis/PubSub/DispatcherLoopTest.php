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

use Predis\Client;
use PredisTestCase;

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
     */
    public function testDispatcherLoopAgainstRedisServer()
    {
        $this->markTestSkippedOnCIEnvironment(
            'Test temporarily skipped on CI environments, see note in the body of the test' // TODO
        );

        $parameters = array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'database' => REDIS_SERVER_DBNUM,
            // Prevents suite from hanging on broken test
            'read_write_timeout' => 2,
        );

        $options = array('profile' => REDIS_SERVER_VERSION);

        $producer = new Client($parameters, $options);
        $producer->connect();

        $consumer = new Client($parameters, $options);
        $consumer->connect();

        $pubsub = new Consumer($consumer);
        $dispatcher = new DispatcherLoop($pubsub);

        $function01 = $this->getMock('stdClass', array('__invoke'));
        $function01->expects($this->exactly(2))
                   ->method('__invoke')
                   ->with($this->logicalOr(
                       $this->equalTo('01:argument'),
                       $this->equalTo('01:quit')
                   ))
                   ->will($this->returnCallback(function ($arg) use ($dispatcher) {
                       if ($arg === '01:quit') {
                           $dispatcher->stop();
                       }
                   }));

        $function02 = $this->getMock('stdClass', array('__invoke'));
        $function02->expects($this->once())
                   ->method('__invoke')
                   ->with('02:argument');

        $function03 = $this->getMock('stdClass', array('__invoke'));
        $function03->expects($this->never())
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
     */
    public function testDispatcherLoopAgainstRedisServerWithPrefix()
    {
        $this->markTestSkippedOnCIEnvironment(
            'Test temporarily skipped on CI environments, see note in the body of the test' // TODO
        );

        $parameters = array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'database' => REDIS_SERVER_DBNUM,
            // Prevents suite from handing on broken test
            'read_write_timeout' => 2,
        );

        $options = array('profile' => REDIS_SERVER_VERSION);

        $producerNonPfx = new Client($parameters, $options);
        $producerNonPfx->connect();

        $producerPfx = new Client($parameters, $options + array('prefix' => 'foobar'));
        $producerPfx->connect();

        $consumer = new Client($parameters, $options + array('prefix' => 'foobar'));

        $pubsub = new Consumer($consumer);
        $dispatcher = new DispatcherLoop($pubsub);

        $callback = $this->getMock('stdClass', array('__invoke'));
        $callback->expects($this->exactly(1))
                 ->method('__invoke')
                 ->with($this->equalTo('arg:prefixed'))
                 ->will($this->returnCallback(function ($arg) use ($dispatcher) {
                     $dispatcher->stop();
                 }));

        $dispatcher->attachCallback('callback', $callback);

        $producerNonPfx->publish('callback', 'arg:non-prefixed');
        $producerPfx->publish('callback', 'arg:prefixed');

        $dispatcher->run();

        $this->assertEquals('PONG', $consumer->ping());
    }
}
