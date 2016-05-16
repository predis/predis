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
class FactoryTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface()
    {
        $factory = new Factory();

        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $factory);
    }

    /**
     * @group disconnected
     */
    public function testSettingDefaultParameters()
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaults = array(
            'password' => 'secret',
            'database' => 10,
            'custom' => 'foobar',
        ));

        $this->assertSame($defaults, $factory->getDefaultParameters());

        $parameters = array('database' => 10, 'persistent' => true);
    }

    /**
     * @group disconnected
     */
    public function testCreateTcpConnection()
    {
        $factory = new Factory();

        $parameters = new Parameters(array('scheme' => 'tcp'));
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertSame($parameters, $connection->getParameters());

        $parameters = new Parameters(array('scheme' => 'redis'));
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertSame($parameters, $connection->getParameters());
    }

    /**
     * @group disconnected
     */
    public function testCreateSslConnection()
    {
        $factory = new Factory();

        $parameters = new Parameters(array('scheme' => 'tls'));
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertSame($parameters, $connection->getParameters());

        $parameters = new Parameters(array('scheme' => 'rediss'));
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertSame($parameters, $connection->getParameters());
    }

    /**
     * @group disconnected
     */
    public function testCreateUnixConnection()
    {
        $factory = new Factory();

        $parameters = new Parameters(array('scheme' => 'unix', 'path' => '/tmp/redis.sock'));
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertSame($parameters, $connection->getParameters());
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithParametersInstanceAndDefaultsDoesNotAlterOriginalParameters()
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'port' => 7000,
            'password' => 'secret',
            'database' => 10,
            'custom' => 'foobar',
        ));

        $inputParams = new Parameters(array(
            'host' => 'localhost',
            'database' => 5,
        ));

        $connection = $factory->create($inputParams);
        $parameters = $connection->getParameters();

        $this->assertEquals('localhost', $parameters->host);
        $this->assertEquals(6379, $parameters->port);
        $this->assertEquals(5, $parameters->database);

        $this->assertFalse(isset($parameters->password));
        $this->assertNull($parameters->password);

        $this->assertFalse(isset($parameters->custom));
        $this->assertNull($parameters->custom);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithNullParameters()
    {
        $factory = new Factory();
        $connection = $factory->create(null);
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
        $this->assertEquals('tcp', $parameters->scheme);

        $this->assertFalse(isset($parameters->custom));
        $this->assertNull($parameters->custom);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithNullParametersAndDefaults()
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'port' => 7000,
            'password' => 'secret',
            'custom' => 'foobar',
        ));

        $connection = $factory->create(null);
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $this->assertEquals('127.0.0.1', $parameters->host);
        $this->assertEquals($defaultParams['port'], $parameters->port);
        $this->assertEquals($defaultParams['password'], $parameters->password);
        $this->assertEquals($defaultParams['custom'], $parameters->custom);
        $this->assertNull($parameters->path);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithArrayParameters()
    {
        $factory = new Factory();
        $connection = $factory->create(array('scheme' => 'tcp', 'custom' => 'foobar'));
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
        $this->assertEquals('tcp', $parameters->scheme);

        $this->assertTrue(isset($parameters->custom));
        $this->assertSame('foobar', $parameters->custom);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithArrayParametersAndDefaults()
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'port' => 7000,
            'password' => 'secret',
            'custom' => 'foobar',
        ));

        $connection = $factory->create($inputParams = array(
            'host' => 'localhost',
            'port' => 8000,
            'persistent' => true,
        ));

        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $this->assertEquals($inputParams['host'], $parameters->host);
        $this->assertEquals($inputParams['port'], $parameters->port);
        $this->assertEquals($defaultParams['password'], $parameters->password);
        $this->assertEquals($defaultParams['custom'], $parameters->custom);
        $this->assertEquals($inputParams['persistent'], $parameters->persistent);
        $this->assertNull($parameters->path);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithStringURI()
    {
        $factory = new Factory();
        $connection = $factory->create('tcp://127.0.0.1?custom=foobar');
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
        $this->assertEquals('tcp', $parameters->scheme);

        $this->assertTrue(isset($parameters->custom));
        $this->assertSame('foobar', $parameters->custom);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithStrinURIAndDefaults()
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'port' => 7000,
            'password' => 'secret',
            'custom' => 'foobar',
        ));

        $connection = $factory->create('tcp://localhost:8000?persistent=1');
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $this->assertEquals('localhost', $parameters->host);
        $this->assertEquals('8000', $parameters->port);
        $this->assertEquals($defaultParams['password'], $parameters->password);
        $this->assertEquals($defaultParams['custom'], $parameters->custom);
        $this->assertEquals(true, $parameters->persistent);
        $this->assertNull($parameters->path);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithoutInitializationCommands()
    {
        $profile = $this->getMock('Predis\Profile\ProfileInterface');
        $profile->expects($this->never())->method('createCommand');

        $factory = new Factory($profile);
        $parameters = new Parameters();
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     *
     * @todo This test smells but there's no other way around it right now.
     */
    public function testCreateConnectionWithInitializationCommands()
    {
        $parameters = new Parameters(array(
            'database' => '0',
            'password' => 'foobar',
        ));

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())
                   ->method('getParameters')
                   ->will($this->returnValue($parameters));
        $connection->expects($this->at(1))
                   ->method('addConnectCommand')
                   ->with($this->isRedisCommand('AUTH', array('foobar')));
        $connection->expects($this->at(2))
                   ->method('addConnectCommand')
                   ->with($this->isRedisCommand('SELECT', array(0)));

        $factory = new Factory();

        $reflection = new \ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expecteExceptionMessage Unknown connection scheme: 'unknown'.
     */
    public function testCreateUndefinedConnection()
    {
        $factory = new Factory();
        $factory->create(new Parameters(array('scheme' => 'unknown')));
    }

    /**
     * @group disconnected
     */
    public function testDefineConnectionWithFQN()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $parameters = new Parameters(array('scheme' => 'foobar'));
        $factory = new Factory();

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

        $parameters = new Parameters(array('scheme' => 'foobar'));

        $initializer = function ($parameters) use ($connectionClass) {
            return new $connectionClass($parameters);
        };

        $initializerMock = $this->getMock('stdClass', array('__invoke'));
        $initializerMock->expects($this->exactly(2))
                        ->method('__invoke')
                        ->with($parameters)
                        ->will($this->returnCallback($initializer));

        $factory = new Factory();
        $factory->define($parameters->scheme, $initializerMock);
        $connection1 = $factory->create($parameters);
        $connection2 = $factory->create($parameters);

        $this->assertInstanceOf($connectionClass, $connection1);
        $this->assertInstanceOf($connectionClass, $connection2);
        $this->assertNotSame($connection1, $connection2);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     */
    public function testDefineConnectionWithInvalidArgument()
    {
        $factory = new Factory();
        $factory->define('foobar', new \stdClass());
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expecteExceptionMessage Unknown connection scheme: 'tcp'.
     */
    public function testUndefineDefinedConnection()
    {
        $factory = new Factory();
        $factory->undefine('tcp');
        $factory->create('tcp://127.0.0.1');
    }

    /**
     * @group disconnected
     */
    public function testUndefineUndefinedConnection()
    {
        $factory = new Factory();
        $factory->undefine('unknown');
        $connection = $factory->create('tcp://127.0.0.1');

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expecteExceptionMessage Unknown connection scheme: 'test'.
     */
    public function testDefineAndUndefineConnection()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $factory = new Factory();

        $factory->define('test', $connectionClass);
        $this->assertInstanceOf($connectionClass, $factory->create('test://127.0.0.1'));

        $factory->undefine('test');
        $factory->create('test://127.0.0.1');
    }

    /**
     * @group disconnected
     */
    public function testAggregateConnectionSkipCreationOnConnectionInstance()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $cluster = $this->getMock('Predis\Connection\Aggregate\ClusterInterface');
        $cluster->expects($this->exactly(2))
                ->method('add')
                ->with($this->isInstanceOf('Predis\Connection\NodeConnectionInterface'));

        $factory = $this->getMock('Predis\Connection\Factory', array('create'));
        $factory->expects($this->never())
                ->method('create');

        $factory->aggregate($cluster, array(new $connectionClass(), new $connectionClass()));
    }

    /**
     * @group disconnected
     */
    public function testAggregateConnectionWithMixedParameters()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $cluster = $this->getMock('Predis\Connection\Aggregate\ClusterInterface');
        $cluster->expects($this->exactly(4))
                ->method('add')
                ->with($this->isInstanceOf('Predis\Connection\NodeConnectionInterface'));

        $factory = $this->getMock('Predis\Connection\Factory', array('create'));
        $factory->expects($this->exactly(3))
                ->method('create')
                ->will($this->returnCallback(function ($_) use ($connectionClass) {
                    return new $connectionClass();
                }));

        $factory->aggregate($cluster, array(null, 'tcp://127.0.0.1', array('scheme' => 'tcp'), new $connectionClass()));
    }

    /**
     * @group disconnected
     */
    public function testAggregateConnectionWithEmptyListOfParameters()
    {
        $cluster = $this->getMock('Predis\Connection\Aggregate\ClusterInterface');
        $cluster->expects($this->never())->method('add');

        $factory = $this->getMock('Predis\Connection\Factory', array('create'));
        $factory->expects($this->never())->method('create');

        $factory->aggregate($cluster, array());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a mocked Predis\Connection\NodeConnectionInterface.
     *
     * @return array Mock instance and class name
     */
    protected function getMockConnectionClass()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');

        return array($connection, get_class($connection));
    }
}
