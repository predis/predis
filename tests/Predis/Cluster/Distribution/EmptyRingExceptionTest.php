<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster\Distribution;

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 * @todo Not really useful right now.
 */
class EmptyRingExceptionTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testExceptionMessage()
    {
        $message = 'Empty Ring';
        $this->setExpectedException('Predis\Cluster\Distribution\EmptyRingException', $message);

        throw new EmptyRingException($message);
    }
}
