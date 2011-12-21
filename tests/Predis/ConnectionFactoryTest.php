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

/**
 *
 */
class ConnectionFactoryTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface()
    {
        $factory = new ConnectionFactory();

        $this->assertInstanceOf('Predis\IConnectionFactory', $factory);
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
        $this->assertInstanceOf('Predis\Network\StreamConnection', $connection);
        $this->assertEquals($tcp->scheme, $parameters->scheme);
        $this->assertEquals($tcp->host, $parameters->host);
        $this->assertEquals($tcp->database, $parameters->database);


        $unix = new ConnectionParameters(array(
            'scheme' => 'unix',
            'path' => '/tmp/redis.sock',
        ));

        $connection = $factory->create($tcp);
        $parameters = $connection->getParameters();
        $this->assertInstanceOf('Predis\Network\StreamConnection', $connection);
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

        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);
        $this->assertEquals('tcp', $parameters->scheme);
        $this->assertFalse($parameters->isSetByUser('custom'));
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithArrayParameters()
    {
        $factory = new ConnectionFactory();
        $connection = $factory->create(array('scheme' => 'tcp', 'custom' => 'foobar'));
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);
        $this->assertEquals('tcp', $parameters->scheme);
        $this->assertTrue($parameters->isSetByUser('custom'));
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithStringURI()
    {
        $factory = new ConnectionFactory();
        $connection = $factory->create('tcp://127.0.0.1?custom=foobar');
        $parameters = $connection->getParameters();

        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);
        $this->assertEquals('tcp', $parameters->scheme);
        $this->assertTrue($parameters->isSetByUser('custom'));
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithoutInitializationCommands()
    {
        $profile = $this->getMock('Predis\Profiles\IServerProfile');
        $profile->expects($this->never())->method('create');

        $factory = new ConnectionFactory();
        $parameters = new ConnectionParameters();
        $connection = $factory->create($parameters, $profile);

        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);
    }

    /**
     * @group disconnected
     */
    public function testCreateConnectionWithInitializationCommands()
    {
        $test = $this;
        $database = 15;
        $password = 'foobar';
        $commands = array();

        $createCommand = function($id, $arguments) use($test, &$commands) {
            $commands[$id] = $arguments;
            return $test->getMock('Predis\Commands\ICommand');
        };

        $profile = $this->getMock('Predis\Profiles\IServerProfile');
        $profile->expects($this->exactly(2))
                ->method('createCommand')
                ->with($this->isType('string'), $this->isType('array'))
                ->will($this->returnCallback($createCommand));

        $factory = new ConnectionFactory();
        $parameters = new ConnectionParameters(array('database' => $database, 'password' => $password));
        $connection = $factory->create($parameters, $profile);

        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);
        $this->assertEquals(2, count($commands));   // TODO: assertCount()?
        $this->assertEquals(array($database), $commands['select']);
        $this->assertEquals(array($password), $commands['auth']);
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

        $initializer = function($parameters) use($connectionClass) {
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

        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);
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
    public function testClusterSkipCreationOnConnectionInstance()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $cluster = $this->getMock('Predis\Network\IConnectionCluster');
        $cluster->expects($this->exactly(2))
                ->method('add')
                ->with($this->isInstanceOf('Predis\Network\IConnectionSingle'));

        $factory = $this->getMock('Predis\ConnectionFactory', array('create'));
        $factory->expects($this->never())
                ->method('create');

        $factory->createCluster($cluster, array(new $connectionClass(), new $connectionClass()));
    }

    /**
     * @group disconnected
     */
    public function testClusterWithMixedConnectionParameters()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $cluster = $this->getMock('Predis\Network\IConnectionCluster');
        $cluster->expects($this->exactly(4))
                ->method('add')
                ->with($this->isInstanceOf('Predis\Network\IConnectionSingle'));

        $factory = $this->getMock('Predis\ConnectionFactory', array('create'));
        $factory->expects($this->exactly(3))
                ->method('create')
                ->will($this->returnCallback(function($_, $_) use($connectionClass) {
                    return new $connectionClass;
                }));

        $factory->createCluster($cluster, array(null, 'tcp://127.0.0.1', array('scheme' => 'tcp'), new $connectionClass()));
    }

    /**
     * @group disconnected
     */
    public function testClusterWithEmptyListOfParameters()
    {
        $cluster = $this->getMock('Predis\Network\IConnectionCluster');
        $cluster->expects($this->never())->method('add');

        $factory = $this->getMock('Predis\ConnectionFactory', array('create'));
        $factory->expects($this->never())->method('create');

        $factory->createCluster($cluster, array());
    }

    /**
     * @group disconnected
     * @todo We might want to add a test for IConnectionSingle::pushInitCommand().
     */
    public function testClusterWithServerProfileArgument()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $cluster = $this->getMock('Predis\Network\IConnectionCluster');
        $profile = $this->getMock('Predis\Profiles\IServerProfile');

        $factory = $this->getMock('Predis\ConnectionFactory', array('create'));
        $factory->expects($this->exactly(2))
                ->method('create')
                ->with($this->anything(), $profile)
                ->will($this->returnCallback(function($_, $_) use($connectionClass) {
                    return new $connectionClass();
                }));

        $nodes = array('tcp://127.0.0.1:7001?password=foo', 'tcp://127.0.0.1:7002?password=bar');
        $factory->createCluster($cluster, $nodes, $profile);
    }

    /**
     * @group disconnected
     */
    public function testReplicationWithMixedConnectionParameters()
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        $replication = $this->getMock('Predis\Network\IConnectionReplication');
        $replication->expects($this->exactly(4))
                    ->method('add')
                    ->with($this->isInstanceOf('Predis\Network\IConnectionSingle'));

        $factory = $this->getMock('Predis\ConnectionFactory', array('create'));
        $factory->expects($this->exactly(3))
                ->method('create')
                ->will($this->returnCallback(function($_, $_) use($connectionClass) {
                    return new $connectionClass;
                }));

        $factory->createReplication($replication, array(null, 'tcp://127.0.0.1', array('scheme' => 'tcp'), new $connectionClass()));
    }


    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a mocked Predis\Network\IConnectionSingle.
     *
     * @return Array Mock instance and class name
     */
    protected function getMockConnectionClass()
    {
        $connection = $this->getMock('Predis\Network\IConnectionSingle');

        return array($connection, get_class($connection));
    }
}
