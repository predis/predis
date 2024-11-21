<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use Predis\Client;
use Predis\Command\RawCommand;
use PredisTestCase;
use ReflectionObject;
use stdClass;

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

        $factory->setDefaultParameters($defaults = [
            'password' => 'secret',
            'database' => 10,
            'custom' => 'foobar',
        ]);

        $this->assertSame($defaults, $factory->getDefaultParameters());

        $parameters = ['database' => 10, 'persistent' => true];
    }

    /**
     * @group disconnected
     */
    public function testCreateTcpConnection(): void
    {
        $factory = new Factory();

        $parameters = new Parameters(['scheme' => 'tcp']);
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertSame($parameters, $connection->getParameters());

        $parameters = new Parameters(['scheme' => 'redis']);
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

        $parameters = new Parameters(['scheme' => 'tls']);
        $connection = $factory->create($parameters);

        $this->assertInstanceOf('Predis\Connection\StreamConnection', $connection);
        $this->assertSame($parameters, $connection->getParameters());

        $parameters = new Parameters(['scheme' => 'rediss']);
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

        $parameters = new Parameters(['scheme' => 'unix', 'path' => '/tmp/redis.sock']);
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

        $factory->setDefaultParameters($defaultParams = [
            'port' => 7000,
            'password' => 'secret',
            'database' => 10,
            'custom' => 'foobar',
        ]);

        $inputParams = new Parameters([
            'host' => 'localhost',
            'database' => 5,
        ]);

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

        $factory->setDefaultParameters($defaultParams = [
            'port' => 7000,
            'password' => 'secret',
            'custom' => 'foobar',
        ]);

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
        $connection = $factory->create(['scheme' => 'tcp', 'custom' => 'foobar']);
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

        $factory->setDefaultParameters($defaultParams = [
            'port' => 7000,
            'password' => 'secret',
            'custom' => 'foobar',
        ]);

        $connection = $factory->create($inputParams = [
            'host' => 'localhost',
            'port' => 8000,
            'persistent' => true,
        ]);

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

        $factory->setDefaultParameters($defaultParams = [
            'port' => 7000,
            'password' => 'secret',
            'custom' => 'foobar',
        ]);

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

        $parameters = new Parameters(['scheme' => 'test']);

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
        $parameters = new Parameters([
            'database' => '0',
            'password' => 'foobar',
        ]);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $connection
            ->expects($this->exactly(2))
            ->method('addConnectCommand')
            ->withConsecutive(
                [$this->isRedisCommand('AUTH', ['foobar'])],
                [$this->isRedisCommand('SELECT', ['0'])]
            );

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithPasswordAndNoUsernameAddsInitializationCommandAuthWithOneArgument()
    {
        $parameters = new Parameters([
            'password' => 'foobar',
        ]);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $connection->expects($this->once())
            ->method('addConnectCommand')
            ->with($this->isRedisCommand('AUTH', ['foobar']));

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithPasswordAndUsernameAddsInitializationCommandAuthWithTwoArguments()
    {
        $parameters = new Parameters([
            'username' => 'myusername',
            'password' => 'foobar',
        ]);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $connection->expects($this->once())
            ->method('addConnectCommand')
            ->with($this->isRedisCommand('AUTH', ['myusername', 'foobar']));

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new ReflectionObject($factory);
        $prepareConnection = $reflection->getMethod('prepareConnection');
        $prepareConnection->setAccessible(true);
        $prepareConnection->invoke($factory, $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithUsernameAndNoPasswordDoesNotAddInitializationCommands()
    {
        $parameters = new Parameters([
            'username' => 'myusername',
        ]);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $connection->expects($this->never())
            ->method('addConnectCommand');

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new ReflectionObject($factory);
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
        $parameters = new Parameters([
            $parameter => $value,
        ]);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($parameters));
        $connection->expects($this->never())
            ->method('addConnectCommand');

        $factory = new Factory();

        // TODO: using reflection to make a protected method accessible :facepalm:
        $reflection = new ReflectionObject($factory);
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
        $factory->create(new Parameters(['scheme' => 'unknown']));
    }

    /**
     * @group disconnected
     */
    public function testDefineConnectionWithFQN(): void
    {
        [, $connectionClass] = $this->getMockConnectionClass();

        $parameters = new Parameters(['scheme' => 'foobar']);
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
        [, $connectionClass] = $this->getMockConnectionClass();

        $parameters = new Parameters(['scheme' => 'foobar']);
        $factory = new Factory();

        $initializer = function ($parameters) use ($connectionClass) {
            return new $connectionClass($parameters);
        };

        $initializerMock = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
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
        $factory->define('foobar', new stdClass());
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

        [, $connectionClass] = $this->getMockConnectionClass();

        $factory = new Factory();

        $factory->define('test', $connectionClass);
        $this->assertInstanceOf($connectionClass, $factory->create('test://127.0.0.1'));

        $factory->undefine('test');
        $factory->create('test://127.0.0.1');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetClientNameAndVersionOnConnection(): void
    {
        $parameters = ['client_info' => true];

        $factory = new Factory();
        $connection = $factory->create($parameters);
        $initCommands = $connection->getInitCommands();

        $this->assertInstanceOf(RawCommand::class, $initCommands[0]);
        $this->assertSame('CLIENT', $initCommands[0]->getId());
        $this->assertSame(['SETINFO', 'LIB-NAME', 'predis'], $initCommands[0]->getArguments());

        $this->assertInstanceOf(RawCommand::class, $initCommands[1]);
        $this->assertSame('CLIENT', $initCommands[1]->getId());
        $this->assertSame(['SETINFO', 'LIB-VER', Client::VERSION], $initCommands[1]->getArguments());
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

        return [$connection, get_class($connection)];
    }

    /**
     * Provides empty values for specific parameters.
     *
     * These parameters usually trigger the addition of initialization commands
     * to connection instances like `password` => AUTH and `database` => SELECT,
     * but they should not be added when their values are NULL or empty strings.
     *
     * @return array
     */
    public function provideEmptyParametersForInitializationCommands()
    {
        return [
            // AUTH
            ['username', ''],
            ['username', null],
            ['password', ''],
            ['password', null],

            // SELECT
            ['database', ''],
            ['database', null],
        ];
    }
}
