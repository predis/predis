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

namespace Predis\Command\Redis\Container;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Predis\Command\Redis\Container\Search\FTCONFIG;
use UnexpectedValueException;

class ContainerFactoryTest extends TestCase
{
    /**
     * @var MockObject|ClientInterface
     */
    private $mockClient;

    /**
     * @var ContainerFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->factory = new ContainerFactory();
    }

    /**
     * @dataProvider containerProvider
     * @param  string $containerCommandId
     * @param  string $expectedContainerClass
     * @return void
     */
    public function testCreatesReturnsExistingCommandContainerClass(
        string $containerCommandId,
        string $expectedContainerClass
    ): void {
        $expectedContainer = new $expectedContainerClass($this->mockClient);

        $this->assertEquals(
            $expectedContainer,
            $this->factory::create($this->mockClient, $containerCommandId)
        );
    }

    /**
     * @dataProvider unexpectedValuesProvider
     * @param  string $containerCommandId
     * @param  string $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionOnNonExistingCommand(
        string $containerCommandId,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->factory::create($this->mockClient, $containerCommandId);
    }

    public function containerProvider(): array
    {
        return [
            'core command' => ['function', FunctionContainer::class],
            'module command' => ['ftconfig', FTCONFIG::class],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'not supported module container command' => ['ftfoobar', 'Given module container command is not supported.'],
            'not supported core container command' => ['foobar', 'Given container command is not supported.'],
        ];
    }
}
