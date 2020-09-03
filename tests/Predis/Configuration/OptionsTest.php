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
 * @todo Use mock objects to test the inner workings of the Options class.
 */
class OptionsTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithoutArguments(): void
    {
        $options = new Options();

        $this->assertTrue($options->exceptions);
        $this->assertNull($options->prefix);
        $this->assertNull($options->aggregate);
        $this->assertInstanceOf('Closure', $options->cluster);
        $this->assertInstanceOf('Closure', $options->replication);
        $this->assertInstanceOf('Predis\Command\FactoryInterface', $options->commands);
        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $options->connections);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayArgument(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->any())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($connection);

        $options = new Options(array(
            'exceptions' => false,
            'prefix' => 'prefix:',
            'commands' => $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock(),
            'connections' => $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock(),
            'cluster' => $callable,
            'replication' => $callable,
            'aggregate' => $callable,
        ));

        $this->assertIsBool($options->exceptions);
        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $options->prefix);
        $this->assertInstanceOf('Predis\Command\FactoryInterface', $options->commands);
        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $options->connections);

        $this->assertInstanceOf('Closure', $initializer = $options->aggregate);
        $this->assertSame($connection, $initializer($options, array()));

        $this->assertInstanceOf('Closure', $initializer = $options->cluster);
        $this->assertSame($connection, $initializer($options, array()));

        $this->assertInstanceOf('Closure', $initializer = $options->replication);
        $this->assertSame($connection, $initializer($options, array()));
    }

    /**
     * @group disconnected
     */
    public function testSupportsCustomOptions(): void
    {
        $options = new Options(array(
            'custom' => 'foobar',
        ));

        $this->assertSame('foobar', $options->custom);
    }

    /**
     * @group disconnected
     */
    public function testUndefinedOptionsReturnNull(): void
    {
        $options = new Options();

        $this->assertFalse($options->defined('unknown'));
        $this->assertFalse(isset($options->unknown));
        $this->assertNull($options->unknown);
    }

    /**
     * @group disconnected
     */
    public function testCanCheckOptionsIfDefinedByUser(): void
    {
        $options = new Options(array(
            'prefix' => 'prefix:',
            'custom' => 'foobar',
            'void' => null,
        ));

        $this->assertTrue($options->defined('prefix'));
        $this->assertTrue($options->defined('custom'));
        $this->assertTrue($options->defined('void'));
        $this->assertFalse($options->defined('commands'));
    }

    /**
     * @group disconnected
     */
    public function testIsSetReplicatesPHPBehavior(): void
    {
        $options = new Options(array(
            'prefix' => 'prefix:',
            'custom' => 'foobar',
            'void' => null,
        ));

        $this->assertTrue(isset($options->prefix));
        $this->assertTrue(isset($options->custom));
        $this->assertFalse(isset($options->void));
        $this->assertFalse(isset($options->commands));
    }

    /**
     * @group disconnected
     */
    public function testReturnsDefaultValueOfSpecifiedOption(): void
    {
        $options = new Options();

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $options->getDefault('commands'));
    }

    /**
     * @group disconnected
     */
    public function testReturnsNullAsDefaultValueForUndefinedOption(): void
    {
        $options = new Options();

        $this->assertNull($options->getDefault('unknown'));
    }

    /**
     * @group disconnected
     */
    public function testLazilyInitializesOptionValueUsingObjectWithInvokeMagicMethod(): void
    {
        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();

        // NOTE: closure values are covered by this test since they define __invoke().
        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($commands);

        $options = new Options(array(
            'commands' => $callable,
        ));

        $this->assertSame($commands, $options->commands);
        $this->assertSame($commands, $options->commands);
    }

    /**
     * @group disconnected
     */
    public function testLazilyInitializesCustomOptionValueUsingObjectWithInvokeMagicMethod(): void
    {
        $custom = new \stdClass();

        // NOTE: closure values are covered by this test since they define __invoke().
        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($custom);

        $options = new Options(array(
            'custom' => $callable,
        ));

        $this->assertSame($custom, $options->custom);
        $this->assertSame($custom, $options->custom);
    }

    /**
     * @group disconnected
     */
    public function testChecksForInvokeMagicMethodDoesNotTriggerAutoloader(): void
    {
        $trigger = $this->getMockBuilder('stdClass')
            ->addMethods(array('autoload'))
            ->getMock();
        $trigger
            ->expects($this->never())
            ->method('autoload');

        spl_autoload_register($autoload = function ($class) use ($trigger) {
            $trigger->autoload($class);
        }, true, false);

        try {
            $options = new Options(array('custom' => 'value'));
            $pfx = $options->prefix;
        } catch (\Exception $_) {
            spl_autoload_unregister($autoload);
        }
    }
}
