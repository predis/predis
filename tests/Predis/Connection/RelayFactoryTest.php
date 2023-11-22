<?php

namespace Predis\Connection;

use PHPUnit\Framework\TestCase;
use Predis\Command\RawCommand;
use Predis\NotSupportedException;

/**
 * @group ext-relay
 * @requires extension relay
 */
class RelayFactoryTest extends TestCase
{
    /**
     * @var RelayFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new RelayFactory();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDefine(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Does not allow to override existing initializer.');

        $this->factory->define('foo', 'bar');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testUndefine(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Does not allow to override existing initializer.');

        $this->factory->undefine('foo');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testCreatesConnectionWithParametersAsObject(): void
    {
        $connection = $this->factory->create(new Parameters());

        $this->assertInstanceOf(RelayConnection::class, $connection);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testCreatesConnectionWithParametersAsArray(): void
    {
        $connection = $this->factory->create([]);

        $this->assertInstanceOf(RelayConnection::class, $connection);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testCreatesConnectionWithInitCommands(): void
    {
        $connection = $this->factory->create(['username' => 'foo', 'password' => 'bar', 'database' => 15]);

        $this->assertInstanceOf(RelayConnection::class, $connection);
        $this->assertEquals(new RawCommand('AUTH', ['foo', 'bar']), $connection->getInitCommands()[0]);
        $this->assertEquals(new RawCommand('SELECT', [15]), $connection->getInitCommands()[1]);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testCreateThrowsExceptionOnUnexpectedScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown connection scheme: 'foobar'.");

        $this->factory->create(['scheme' => 'foobar']);
    }
}
