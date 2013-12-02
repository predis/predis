<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use PredisTestCase;

/**
 *
 */
class ConnectionFactoryTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface()
    {
        $factory = new ConnectionFactory();

        $this->assertInstanceOf('Predis\Connection\ConnectionFactoryInterface', $factory);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnection()
    {
        $factory = new ConnectionFactory();

        $tcp = new ConnectionParameters(array(
            'scheme' => 'tcp',
            'host' => 'locahost',
        ));

        $connection = $factory->create($tcp);
        $parameters = $connection->getParameters();
        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertEquals($tcp->scheme, $parameters->scheme);
        $this->assertEquals($tcp->host, $parameters->host);
        $this->assertEquals($tcp->database, $parameters->database);


        $unix = new ConnectionParameters(array(
            'scheme' => 'unix',
            'path' => '/tmp/redis.sock',
        ));

        $connection = $factory->create($tcp);
        $parameters = $connection->getParameters();
        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertEquals($tcp->scheme, $parameters->scheme);
        $this->assertEquals($tcp->database, $parameters->database);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithNullParameters()
    {
        $factory = new ConnectionFactory();
        $connection = $factory->create(null);
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\SingleConnectionInterface', $connection);
        $this->assertEquals('tcp', $parameters->scheme);

        $this->assertFalse(isset($parameters->custom));
        $this->assertNull($parameters->custom);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithArrayParameters()
    {
        $factory = new ConnectionFactory();
        $connection = $factory->create(array('scheme' => 'tcp', 'custom' => 'foobar'));
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\SingleConnectionInterface', $connection);
        $this->assertEquals('tcp', $parameters->scheme);

        $this->assertTrue(isset($parameters->custom));
        $this->assertSame('foobar', $parameters->custom);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithStringURI()
    {
        $factory = new ConnectionFactory();
        $connection = $factory->create('tcp://127.0.0.1?custom=foobar');
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\SingleConnectionInterface', $connection);
        $this->assertEquals('tcp', $parameters->scheme);

        $this->assertTrue(isset($parameters->custom));
        $this->assertSame('foobar', $parameters->custom);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithoutInitializationCommands()
    {
        $profile = $this->getMock('Predis\Profile\ProfileInterface');
        $profile->expects($this->never())->method('create');

        $factory = new ConnectionFactory($profile);
        $parameters = new ConnectionParameters();
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\SingleConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @todo This test smells but there's no other way around it right now.
     */
    public function testCreateConnectionWithInitializationCommands()
    {
        $parameters = new ConnectionParameters(array(
            'database' => '0',
            'password' => 'foobar'
        ));

        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');
        $connection->expects($this->once())
                   ->method('getParameters')
                   ->will($this->returnValue($parameters));
        $connection->expects($this->at(1))
                   ->method('pushInitCommand')
                   ->with($this->isRedisCommand('AUTH', array('foobar')));
        $connection->expects($this->at(2))
                   ->method('pushInitCommand')
                   ->with($this->isRedisCommand('SELECT', array(0)));

        $factory = new ConnectionFactory();

        $reflection = new \ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateUndefinedConnection()
    {
        $scheme = 'unknown';
        $this->setExpectedException('InvalidArgumentException', "Unknown connection scheme: $scheme");

        $factory = new ConnectionFactory();
        $factory->create(new ConnectionParameters(array('scheme' => $scheme)));
    }

    /**
     * @group disconnected
     */
    public function testDefineConnectionWithFQN()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $parameters = new ConnectionParameters(array('scheme' => 'foobar'));
        $factory = new ConnectionFactory();

        $factory->define($parameters->scheme, $connectionClass);
        $connection = $factory->create($parameters);

        $this->assertInstanceOf($connectionClass, $connection);
    }

    /**
     * @group disconnected
     */
    public function testDefineConnectionWithCallable()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $parameters = new ConnectionParameters(array('scheme' => 'foobar'));

        $initializer = function ($parameters) use ($connectionClass) {
            return new $connectionClass($parameters);
        };

        $initializerMock = $this->getMock('stdClass', array('__invoke'));
        $initializerMock->expects($this->exactly(2))
                        ->method('__invoke')
                        ->with($parameters)
                        ->will($this->returnCallback($initializer));

        $factory = new ConnectionFactory();
        $factory->define($parameters->scheme, $initializerMock);
        $connection1 = $factory->create($parameters);
        $connection2 = $factory->create($parameters);

        $this->assertInstanceOf($connectionClass, $connection1);
        $this->assertInstanceOf($connectionClass, $connection2);
        $this->assertNotSame($connection1, $connection2);
    }

    /**
     * @group disconnected
     */
    public function testDefineConnectionWithInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException');

        $factory = new ConnectionFactory();
        $factory->define('foobar', new \stdClass());
    }

    /**
     * @group disconnected
     */
    public function testUndefineDefinedConnection()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unknown connection scheme: tcp');

        $factory = new ConnectionFactory();
        $factory->undefine('tcp');
        $factory->create('tcp://127.0.0.1');
    }

    /**
     * @group disconnected
     */
    public function testUndefineUndefinedConnection()
    {
        $factory = new ConnectionFactory();
        $factory->undefine('unknown');
        $connection = $factory->create('tcp://127.0.0.1');

        $this->assertInstanceOf('Predis\Connection\SingleConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testDefineAndUndefineConnection()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $factory = new ConnectionFactory();

        $factory->define('redis', $connectionClass);
        $this->assertInstanceOf($connectionClass, $factory->create('redis://127.0.0.1'));

        $factory->undefine('redis');
        $this->setExpectedException('InvalidArgumentException', 'Unknown connection scheme: redis');
        $factory->create('redis://127.0.0.1');
    }

    /**
     * @group disconnected
     */
    public function testAggregatedConnectionSkipCreationOnConnectionInstance()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $cluster = $this->getMock('Predis\Connection\ClusterConnectionInterface');
        $cluster->expects($this->exactly(2))
                ->method('add')
                ->with($this->isInstanceOf('Predis\Connection\SingleConnectionInterface'));

        $factory = $this->getMock('Predis\Connection\ConnectionFactory', array('create'));
        $factory->expects($this->never())
                ->method('create');

        $factory->aggregate($cluster, array(new $connectionClass(), new $connectionClass()));
    }

    /**
     * @group disconnected
     */
    public function testAggregatedConnectionWithMixedParameters()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $cluster = $this->getMock('Predis\Connection\ClusterConnectionInterface');
        $cluster->expects($this->exactly(4))
                ->method('add')
                ->with($this->isInstanceOf('Predis\Connection\SingleConnectionInterface'));

        $factory = $this->getMock('Predis\Connection\ConnectionFactory', array('create'));
        $factory->expects($this->exactly(3))
                ->method('create')
                ->will($this->returnCallback(function ($_) use ($connectionClass) {
                    return new $connectionClass;
                }));

        $factory->aggregate($cluster, array(null, 'tcp://127.0.0.1', array('scheme' => 'tcp'), new $connectionClass()));
    }

    /**
     * @group disconnected
     */
    public function testAggregatedConnectionWithEmptyListOfParameters()
    {
        $cluster = $this->getMock('Predis\Connection\ClusterConnectionInterface');
        $cluster->expects($this->never())->method('add');

        $factory = $this->getMock('Predis\Connection\ConnectionFactory', array('create'));
        $factory->expects($this->never())->method('create');

        $factory->aggregate($cluster, array());
    }


    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a mocked Predis\Connection\SingleConnectionInterface.
     *
     * @return array Mock instance and class name
     */
    protected function getMockConnectionClass()
    {
        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');

        return array($connection, get_class($connection));
    }
}
