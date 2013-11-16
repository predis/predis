<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Response;

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 *
 */
class StatusQueuedTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testResponseQueuedClass()
    {
        $queued = new StatusQueued();

        $this->assertInstanceOf('Predis\Response\ObjectInterface', $queued);
    }

    /**
     * @group disconnected
     */
    public function testToString()
    {
        $queued = new StatusQueued();

        $this->assertEquals('QUEUED', (string) $queued);
    }

    /**
     * @group disconnected
     */
    public function testQueuedProperty()
    {
        $queued = new StatusQueued();

        $this->assertTrue(isset($queued->queued));
        $this->assertTrue($queued->queued);
    }
}
