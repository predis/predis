<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Container;

use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use UnexpectedValueException;

class ContainerFactoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ClientInterface
     */
    private $mockClient;

    /**
     * @var ContainerFactory
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $expectedContainer;

    protected function setUp(): void
    {
        $this->mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->expectedContainer = new FunctionContainer($this->mockClient);
        $this->factory = new ContainerFactory();
    }

    /**
     * @return void
     */
    public function testCreatesReturnsExistingCommandContainerClass(): void
    {
        $this->assertEquals(
            $this->expectedContainer,
            $this->factory::create($this->mockClient, 'function')
        );
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnNonExistingCommand(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Given command is not supported.');

        $this->factory::create($this->mockClient, 'foobar');
    }
}
