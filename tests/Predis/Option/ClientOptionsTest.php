<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Option;

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 * @todo We should test the inner work performed by this class
 *       using mock objects, but it is quite hard to to that.
 */
class ClientOptionsTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithoutArguments()
    {
        $options = new ClientOptions();

        $this->assertInstanceOf('Predis\ConnectionFactoryInterface', $options->connections);
        $this->assertInstanceOf('Predis\Profile\ServerProfileInterface', $options->profile);
        $this->assertInstanceOf('Predis\Connection\ClusterConnectionInterface', $options->cluster);
        $this->assertNull($options->prefix);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayArgument()
    {
        $options = new ClientOptions(array(
            'cluster' => 'Predis\Connection\PredisCluster',
            'connections' => 'Predis\ConnectionFactory',
            'prefix' => 'prefix:',
            'profile' => '2.0',
        ));

        $this->assertInstanceOf('Predis\ConnectionFactoryInterface', $options->connections);
        $this->assertInstanceOf('Predis\Profile\ServerProfileInterface', $options->profile);
        $this->assertInstanceOf('Predis\Connection\ClusterConnectionInterface', $options->cluster);
        $this->assertInstanceOf('Predis\Command\Processor\CommandProcessorInterface', $options->prefix);
    }

    /**
     * @group disconnected
     */
    public function testHandlesCustomOptionsWithoutHandlers()
    {
        $options = new ClientOptions(array(
            'custom' => 'foobar',
        ));

        $this->assertSame('foobar', $options->custom);
    }

    /**
     * @group disconnected
     */
    public function testIsSetReturnsIfOptionHasBeenSetByUser()
    {
        $options = new ClientOptions(array(
            'prefix' => 'prefix:',
            'custom' => 'foobar',
        ));

        $this->assertTrue(isset($options->prefix));
        $this->assertTrue(isset($options->custom));
        $this->assertFalse(isset($options->profile));
    }
}
