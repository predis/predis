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

use \PHPUnit_Framework_TestCase as StandardTestCase;
use Predis\Network\IConnectionSingle;

/**
 *
 */
class CommunicationExceptionTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testExceptionMessage()
    {
        $message = 'Connection error message.';
        $connection = $this->getMockedConnectionBase();
        $exception = $this->getException($connection, $message);

        $this->setExpectedException('Predis\CommunicationException', $message);

        throw $exception;
    }

    /**
     * @group disconnected
     */
    public function testExceptionConnection()
    {
        $connection = $this->getMockedConnectionBase();
        $exception = $this->getException($connection, 'ERROR MESSAGE');

        $this->assertSame($connection, $exception->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testShouldResetConnection()
    {
        $connection = $this->getMockedConnectionBase();
        $exception = $this->getException($connection, 'ERROR MESSAGE');

        $this->assertTrue($exception->shouldResetConnection());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a mocked connection instance.
     *
     * @param mixed $parameters Connection parameters.
     * @return IConnectionSingle
     */
    protected function getMockedConnectionBase($parameters = null)
    {
        $builder = $this->getMockBuilder('Predis\Network\ConnectionBase');

        if ($parameters === null) {
            $builder->disableOriginalConstructor();
        }
        else if (!$parameters instanceof IConnectionParameters) {
            $parameters = new ConnectionParameters($parameters);
        }

        return $builder->getMockForAbstractClass(array($parameters));
    }

    /**
     * Returns a connection exception instance.
     *
     * @param IConnectionSingle $message Connection instance.
     * @param string $message Exception message.
     * @param int $code Exception code.
     * @param \Exception $inner Inner exception.
     * @return \Exception
     */
    protected function getException(IConnectionSingle $connection, $message, $code = 0, \Exception $inner = null)
    {
        $arguments = array($connection, $message, $code, $inner);
        $mock = $this->getMockForAbstractClass('Predis\CommunicationException', $arguments);

        return $mock;
    }
}
