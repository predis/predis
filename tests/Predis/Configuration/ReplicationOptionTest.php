<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration;

use PredisTestCase;

/**
 *
 */
class ReplicationOptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new ReplicationOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Connection\ReplicationConnectionInterface', $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\MasterSlaveReplication', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsValuesThatCanBeInterpretedAsBooleans()
    {
        $option = new ReplicationOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertNull($option->filter($options, null));

        $this->assertInstanceOf('Predis\Connection\ReplicationConnectionInterface', $option->filter($options, true));
        $this->assertNull($option->filter($options, false));

        $this->assertInstanceOf('Predis\Connection\ReplicationConnectionInterface', $option->filter($options, 1));
        $this->assertNull($option->filter($options, 0));

        $this->assertInstanceOf('Predis\Connection\ReplicationConnectionInterface', $option->filter($options, 'true'));
        $this->assertNull($option->filter($options, 'false'));

        $this->assertInstanceOf('Predis\Connection\ReplicationConnectionInterface', $option->filter($options, 'on'));
        $this->assertNull($option->filter($options, 'off'));
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidInstanceType()
    {
        $option = new ReplicationOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $value = $this->getMock('Predis\Connection\SingleConnectionInterface');

        $option->filter($options, $value);
    }
}
