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
use Predis\Response;
use PredisTestCase;

/**
 *
 */
class AtomicTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testPipelineWithSingleConnection(): void
    {
        $pong = new Response\Status('PONG');
        $queued = new Response\Status('QUEUED');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                array($this->isRedisCommand('MULTI')),
                array($this->isRedisCommand('EXEC'))
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                array($pong, $pong, $pong)
            );
        $connection
            ->expects($this->exactly(3))
            ->method('writeRequest')
            ->withConsecutive(
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING'))
            );
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls(
                $queued,
                $queued,
                $queued
            );

        $pipeline = new Atomic(new Client($connection));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame(array($pong, $pong, $pong), $pipeline->execute());
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnAbortedTransaction(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('The underlying transaction has been aborted by the server');

        $queued = new Response\Status('QUEUED');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                array($this->isRedisCommand('MULTI')),
                array($this->isRedisCommand('EXEC'))
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                null
            );
        $connection
            ->expects($this->exactly(3))
            ->method('writeRequest')
            ->withConsecutive(
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING'))
            );
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls(
                $queued,
                $queued,
                $queued
            );

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
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR Test error');

        $queued = new Response\Status('QUEUED');
        $error = new Response\Error('ERR Test error');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                array($this->isRedisCommand('MULTI')),
                array($this->isRedisCommand('DISCARD'))
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                new Response\Status('OK')
            );
        $connection
            ->expects($this->exactly(3))
            ->method('writeRequest')
            ->withConsecutive(
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING'))
            );
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls(
                $queued,
                $queued,
                $error
            );

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
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR Test error');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                array($this->isRedisCommand('MULTI')),
                array($this->isRedisCommand('DISCARD'))
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                new Response\Status('OK')
            );
        $connection
            ->expects($this->exactly(2))
            ->method('writeRequest')
            ->withConsecutive(
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING'))
            );
        $connection
            ->expects($this->once())
            ->method('readResponse')
            ->willReturn(
                new Response\Error('ERR Test error')
            );

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
        $pong = new Response\Status('PONG');
        $queued = new Response\Status('QUEUED');
        $error = new Response\Error('ERR Test error');

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                array($this->isRedisCommand('MULTI')),
                array($this->isRedisCommand('EXEC'))
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                array($pong, $pong, $error)
            );
        $connection
            ->expects($this->exactly(3))
            ->method('writeRequest')
            ->withConsecutive(
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING')),
                array($this->isRedisCommand('PING'))
            );
        $connection
            ->expects($this->exactly(3))
            ->method('readResponse')
            ->willReturnOnConsecutiveCalls(
                $queued,
                $queued,
                $queued
            );

        $pipeline = new Atomic(new Client($connection, array('exceptions' => false)));

        $pipeline->ping();
        $pipeline->ping();
        $pipeline->ping();

        $this->assertSame(array($pong, $pong, $error), $pipeline->execute());
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
}
