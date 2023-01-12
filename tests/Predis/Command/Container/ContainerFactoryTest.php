<?php

namespace Predis\Command\Container;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Predis\Command\Redis\Container\ContainerFactory;
use Predis\Command\Redis\Container\Json\JSONDEBUG;
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
     * @var JSONDEBUG
     */
    private $expectedCommand;

    protected function setUp(): void
    {
        $this->mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->expectedCommand = new JSONDEBUG($this->mockClient);
        $this->factory = new ContainerFactory();
    }

    /**
     * @return void
     */
    public function testCreatesReturnsExistingCommandContainerClass(): void
    {
        $this->assertEquals(
            $this->expectedCommand,
            $this->factory::create($this->mockClient, 'jsondebug')
        );
    }

    /**
     * @dataProvider unexpectedValueProvider
     * @param string $containerCommandId
     * @param string $expectedException
     * @param string $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionOnNonExistingCommand(
        string $containerCommandId,
        string $expectedException,
        string $expectedExceptionMessage
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->factory::create($this->mockClient, $containerCommandId);
    }

    public function unexpectedValueProvider(): array
    {
        return [
            'non-exisiting module' => [
                'foofunctions',
                InvalidArgumentException::class,
                'Given Redis module is not supported.'
            ],
            'non-existing command' => [
                'jsonbar',
                UnexpectedValueException::class,
                'Given command is not supported.'
            ]
        ];
    }
}
