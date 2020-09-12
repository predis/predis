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
    }

    /**
     * @group disconnected
     */
    public function testSettingDefaultParametersForMasterRole(): void
    {
        $factory = new Factory();

        $factory->setDefaultParameters($expected = array(
            'role.master' => [
                'username' => 'myusername',
                'password' => 'secret',
                'database' => 10,
            ]
        ));

        $this->assertSame($expected, $factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testSettingDefaultParametersForMasterRoleAcceptsArrayOnly(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Default parameters for `role.master` must be passed as a named array');

        $factory = new Factory();
        $factory->setDefaultParameters(array(
            'role.master' => 'invalid value',
        ));
    }

    /**
     * @group disconnected
     */
    public function testSettingDefaultParametersForSlaveRole(): void
    {
        $factory = new Factory();

        $factory->setDefaultParameters($expected = array(
            'role.slave' => [
                'username' => 'myusername',
                'password' => 'secret',
                'database' => 10,
            ]
        ));

        $this->assertSame($expected, $factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testSettingDefaultParametersForSlaveRoleAcceptsArrayOnly(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Default parameters for `role.slave` must be passed as a named array');

        $factory = new Factory();
        $factory->setDefaultParameters(array(
            'role.slave' => 'invalid value',
        ));
    }

    /**
     * @group disconnected
     */
    public function testSettingDefaultParametersForSentinelRoleIgnoresUsernameAndPassword(): void
    {
        $factory = new Factory();

        $factory->setDefaultParameters(array(
            'role.sentinel' => [
                'username' => 'myusername',
                'password' => 'secret',
                'database' => 10,
            ]
        ));

        $expected = array(
            'role.sentinel' => [
                'password' => 'secret',
            ]
        );

        $this->assertSame($expected, $factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testSettingDefaultParametersForSentinelRoleAcceptsArrayOnly(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Default parameters for `role.sentinel` must be passed as a named array');

        $factory = new Factory();
        $factory->setDefaultParameters(array(
            'role.sentinel' => 'invalid value',
        ));
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
    public function testCreateConnectionWithDefaultParametersDoNotOverrideExplicitInputParameters(): void
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
    public function testCreateConnectionForSentinelRoleIgnoresUsernameAndDatabase(): void
    {
        $factory = new Factory();

        $connection = $factory->create($inputParams = array(
            'role' => 'sentinel',
            'username' => 'myusername',
            'password' => 'mypassword',
            'database' => 10,
        ));

        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $this->assertEquals($inputParams['role'], $parameters->role);
        $this->assertEquals($inputParams['password'], $parameters->password);
        $this->assertNull($parameters->username);
        $this->assertNull($parameters->database);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionForSentinelRoleDoesNotInheritPasswordFromGlobalDefaultParameters(): void
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'password' => 'pwd.default',
        ));

        $connectionSentinelRole = $factory->create($inputParamsSentinelRole = array(
            'role' => 'sentinel',
        ));

        $parameters = $connectionSentinelRole->getParameters();
        $this->assertNull($parameters->password);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionForSentinelRoleDoesNotInheritUsernameFromGlobalDefaultParameters(): void
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'username' => 'usr.default',
        ));

        $connectionSentinelRole = $factory->create($inputParamsSentinelRole = array(
            'role' => 'sentinel',
        ));

        $parameters = $connectionSentinelRole->getParameters();
        $this->assertNull($parameters->username);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionForSentinelRoleDoesNotInheritDatabaseFromGlobalDefaultParameters(): void
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'database' => 15,
        ));

        $connectionSentinelRole = $factory->create($inputParamsSentinelRole = array(
            'role' => 'sentinel',
        ));

        $parameters = $connectionSentinelRole->getParameters();
        $this->assertNull($parameters->database);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithDefaultRoleParametersDoNotOverrideExplicitInputParameters(): void
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'timeout' => 20,
            'password' => 'pwd.default.norole',

            'role.master' => [
                'password' => 'pwd.role.master',
                'timeout' => 10,
            ],
            'role.slave' => [
                'password' => 'pwd.role.slave',
                'timeout' => 5,
            ],
            'role.sentinel' => [
                'password' => 'pwd.role.sentinel',
                'timeout' => 1,
            ],
        ));

        // NO ROLE
        $connectionNoRole = $factory->create($inputParamsNoRole = array(
            'password' => 'pwd.local.norole',
            'timeout' => 30,
        ));

        $parameters = $connectionNoRole->getParameters();
        $this->assertEquals('pwd.local.norole', $parameters->password);
        $this->assertEquals(30, $parameters->timeout);

        // ROLE MASTER
        $connectionMasterRole = $factory->create($inputParamsMasterRole = array(
            'role' => 'master',
            'password' => 'pwd.local.master',
            'timeout' => 30,
        ));

        $parameters = $connectionMasterRole->getParameters();
        $this->assertEquals('pwd.local.master', $parameters->password);
        $this->assertEquals(30, $parameters->timeout);

        // ROLE SLAVE
        $connectionSlaveRole = $factory->create($inputParamsSlaveRole = array(
            'role' => 'slave',
            'password' => 'pwd.local.slave',
            'timeout' => 30,
        ));

        $parameters = $connectionSlaveRole->getParameters();
        $this->assertEquals('pwd.local.slave', $parameters->password);
        $this->assertEquals(30, $parameters->timeout);

        // ROLE SENTINEL
        $connectionSentinelRole = $factory->create($inputParamsSentinelRole = array(
            'role' => 'slave',
            'password' => 'pwd.local.sentinel',
            'timeout' => 30,
        ));

        $parameters = $connectionSentinelRole->getParameters();
        $this->assertEquals('pwd.local.sentinel', $parameters->password);
        $this->assertEquals(30, $parameters->timeout);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithDefaultRoleParametersOverridesDefaultGlobalParameters(): void
    {
        $factory = new Factory();

        $factory->setDefaultParameters($defaultParams = array(
            'timeout' => 20,
            'password' => 'pwd.default.norole',

            'role.master' => [
                'password' => 'pwd.role.master',
                'timeout' => 10,
            ],
            'role.slave' => [
                'password' => 'pwd.role.slave',
                'timeout' => 5,
            ],
            'role.sentinel' => [
                'password' => 'pwd.role.sentinel',
                'timeout' => 1,
            ],
        ));

        // NO ROLE
        $connectionNoRole = $factory->create($inputParamsNoRole = array(
            // EMPTY
        ));

        $parameters = $connectionNoRole->getParameters();
        $this->assertEquals('pwd.default.norole', $parameters->password);
        $this->assertEquals(20, $parameters->timeout);

        // ROLE MASTER
        $connectionMasterRole = $factory->create($inputParamsMasterRole = array(
            'role' => 'master',
        ));

        $parameters = $connectionMasterRole->getParameters();
        $this->assertEquals('pwd.role.master', $parameters->password);
        $this->assertEquals(10, $parameters->timeout);

        // ROLE SLAVE
        $connectionSlaveRole = $factory->create($inputParamsSlaveRole = array(
            'role' => 'slave',
        ));

        $parameters = $connectionSlaveRole->getParameters();
        $this->assertEquals('pwd.role.slave', $parameters->password);
        $this->assertEquals(5, $parameters->timeout);

        // ROLE SENTINEL
        $connectionSentinelRole = $factory->create($inputParamsSentinelRole = array(
            'role' => 'sentinel',
        ));

        $parameters = $connectionSentinelRole->getParameters();
        $this->assertEquals('pwd.role.sentinel', $parameters->password);
        $this->assertEquals(1, $parameters->timeout);
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
