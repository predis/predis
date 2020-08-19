<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use PredisTestCase;

/**
 *
 */
class CommunicationExceptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExceptionReturnsInnerConnection()
    {
        $connection = $this->getMockConnection();
        $exception = $this->createMockException($connection, 'Communication error message');

        $this->assertSame($connection, $exception->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testExceptionMessage()
    {
        $connection = $this->getMockConnection();
        $exception = $this->createMockException($connection, $message = 'Connection error message');

        $this->expectException('Predis\CommunicationException');
        $this->expectExceptionMessage($message);

        throw $exception;
    }

    /**
     * @group disconnected
     */
    public function testShouldResetConnectionIsTrue()
    {
        $connection = $this->getMockConnection();
        $exception = $this->createMockException($connection, 'Communication error message');

        $this->assertTrue($exception->shouldResetConnection());
    }

    /**
     * @group disconnected
     */
    public function testCommunicationExceptionHandling()
    {
        $connection = $this->getMockConnection();
        $connection
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $connection
            ->expects($this->once())
            ->method('disconnect');

        $exception = $this->createMockException($connection, $message = 'Communication error message');

        $this->expectException('Predis\CommunicationException');
        $this->expectExceptionMessage($message);

        CommunicationException::handle($exception);
    }

    /**
     * @group disconnected
     */
    public function testCommunicationExceptionHandlingWhenShouldResetConnectionIsFalse()
    {
        $connection = $this->getMockConnection();
        $connection
            ->expects($this->never())
            ->method('isConnected');
        $connection
            ->expects($this->never())
            ->method('disconnect');

        $exception = $this->getMockBuilder('Predis\CommunicationException')
            ->setConstructorArgs(array($connection, 'Communication error message'))
            ->setMethods(array('shouldResetConnection'))
            ->getMockForAbstractClass();
        $exception
            ->expects($this->once())
            ->method('shouldResetConnection')
            ->will($this->returnValue(false));

        $this->expectException('Predis\CommunicationException');
        $this->expectExceptionMessage('Communication error message');

        CommunicationException::handle($exception);
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a connection exception instance.
     *
     * @param Connection\NodeConnectionInterface $connection Connection instance.
     * @param string                             $message    Exception message.
     * @param int                                $code       Exception code.
     * @param \Exception                         $inner      Inner exception.
     *
     * @return \Predis\CommunicationException
     */
    protected function createMockException(
        Connection\NodeConnectionInterface $connection,
        $message,
        $code = 0,
        \Exception $inner = null
    ) {
        return $this->getMockBuilder('Predis\CommunicationException')
            ->setConstructorArgs(array($connection, $message, $code, $inner))
            ->getMockForAbstractClass();
    }
}
