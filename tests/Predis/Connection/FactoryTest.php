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
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Connection\Cluster\ClusterInterface;

/**
 *
 */
class FactoryTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface(): void
    {
        $factory = new Factory();

        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $factory);
    }

    /**
     * @group disconnected
     */
    public function testSettingDefaultParameters(): void
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
    public function testCreateTcpConnection(): void
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
    public function testCreateSslConnection(): void
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
    public function testCreateUnixConnection(): void
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
    public function testCreateConnectionWithParametersInstanceAndDefaultsDoesNotAlterOriginalParameters(): void
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
    public function testCreateConnectionWithNullParameters(): void
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
    public function testCreateConnectionWithNullParametersAndDefaults(): void
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
    public function testCreateConnectionWithArrayParameters(): void
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
    public function testCreateConnectionWithArrayParametersAndDefaults(): void
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
    public function testCreateConnectionWithStringURI(): void
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
    public function testCreateConnectionWithStrinURIAndDefaults(): void
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
    public function testCreateConnectionWithoutInitializationCommands(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('addConnectCommand');

        $parameters = new Parameters(array('scheme' => 'test'));

        $factory = new Factory();
        $factory->define('test', function ($_parameters, $_factory) use ($connection, $parameters, $factory) {
            $this->assertSame($_parameters, $parameters);
            $this->assertSame($_factory, $factory);

            return $connection;
        });

        $this->assertSame($connection, $factory->create($parameters));
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithInitializationCommands(): void
    {
        $parameters = new Parameters(array(
            'database' => '0',
            'password' => 'foobar',
        ));

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $connection
            ->expects($this->exactly(2))
            ->method('addConnectCommand')
            ->withConsecutive(
                array($this->isRedisCommand('AUTH', array('foobar'))),
                array($this->isRedisCommand('SELECT', array('0')))
            );

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new \ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithPasswordAndNoUsernameAddsInitializationCommandAuthWithOneArgument()
    {
        $parameters = new Parameters(array(
            'password' => 'foobar',
        ));

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $connection->expects($this->once(1))
            ->method('addConnectCommand')
            ->with($this->isRedisCommand('AUTH', array('foobar')));

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new \ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithPasswordAndUsernameAddsInitializationCommandAuthWithTwoArguments()
    {
        $parameters = new Parameters(array(
            'username' => 'myusername',
            'password' => 'foobar',
        ));

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $connection->expects($this->once(1))
            ->method('addConnectCommand')
            ->with($this->isRedisCommand('AUTH', array('myusername', 'foobar')));

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new \ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithUsernameAndNoPasswordDoesNotAddInitializationCommands()
    {
        $parameters = new Parameters(array(
            'username' => 'myusername',
        ));

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $connection->expects($this->never())
            ->method('addConnectCommand');

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new \ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     * @dataProvider provideEmptyParametersForInitializationCommands
     */
    public function testCreateConnectionWithEmptyParametersDoesNotAddInitializationCommands($parameter, $value)
    {
        $parameters = new Parameters(array(
            $parameter => $value,
        ));

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $connection->expects($this->never())
            ->method('addConnectCommand');

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new \ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateUndefinedConnection(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Unknown connection scheme: 'unknown'");

        $factory = new Factory();
        $factory->create(new Parameters(array('scheme' => 'unknown')));
    }

    /**
     * @group disconnected
     */
    public function testDefineConnectionWithFQN(): void
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
    public function testDefineConnectionWithCallable(): void
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $parameters = new Parameters(array('scheme' => 'foobar'));
        $factory = new Factory();

        $initializer = function ($parameters) use ($connectionClass) {
            return new $connectionClass($parameters);
        };

        $initializerMock = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $initializerMock
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->with($parameters, $factory)
            ->willReturnCallback($initializer);

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
    public function testDefineConnectionWithInvalidArgument(): void
    {
        $this->expectException('InvalidArgumentException');

        $factory = new Factory();
        $factory->define('foobar', new \stdClass());
    }

    /**
     * @group disconnected
     */
    public function testUndefineDefinedConnection(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Unknown connection scheme: 'tcp'");

        $factory = new Factory();
        $factory->undefine('tcp');
        $factory->create('tcp://127.0.0.1');
    }

    /**
     * @group disconnected
     */
    public function testUndefineUndefinedConnection(): void
    {
        $factory = new Factory();
        $factory->undefine('unknown');
        $connection = $factory->create('tcp://127.0.0.1');

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testDefineAndUndefineConnection(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Unknown connection scheme: 'test'");

        list(, $connectionClass) = $this->getMockConnectionClass();

        $factory = new Factory();

        $factory->define('test', $connectionClass);
        $this->assertInstanceOf($connectionClass, $factory->create('test://127.0.0.1'));

        $factory->undefine('test');
        $factory->create('test://127.0.0.1');
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a mocked Predis\Connection\NodeConnectionInterface.
     *
     * @return array Mock instance of a single node connection and its FQCN
     */
    protected function getMockConnectionClass()
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        return array($connection, get_class($connection));
    }

    /**
     * Provides empty values for specific parameters.
     *
     * These parameters usually trigger the addition of initializatin commands
     * to connection instances like `password` => AUTH and `database` => SELECT,
     * but they should not be added when their values are NULL or empty strings.
     *
     * @return array
     */
    public function provideEmptyParametersForInitializationCommands()
    {
        return array(
            // AUTH
            array('username', ''),
            array('username', null),
            array('password', ''),
            array('password', null),

            // SELECT
            array('database', ''),
            array('database', null),
        );
    }
}
