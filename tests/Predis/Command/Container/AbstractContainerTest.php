<?php

namespace Predis\Command\Container;

use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\Command\Redis\Container\AbstractContainer;

class AbstractContainerTest extends TestCase
{
    /**
     * @var AbstractContainer
     */
    private $testClass;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var array
     */
    private $expectedValue;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ClientInterface
     */
    private $mockClient;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CommandInterface
     */
    private $mockCommand;

    protected function setUp(): void
    {
        $this->arguments = ['arg1', 'arg2'];
        $this->expectedValue = ['value'];
        $this->mockCommand = $this->getMockBuilder(CommandInterface::class)->getMock();
        $this->mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();

        $this->testClass = new class($this->mockClient) extends AbstractContainer {
            protected static $containerId = 'test';
        };
    }

    /**
     * @return void
     */
    public function testGetContainerId(): void
    {
        $this->assertSame('test', $this->testClass->getContainerId());
    }

    /**
     * @return void
     */
    public function testCallReturnsValidCommandResponse(): void
    {
        $modifiedArguments = ['TEST', ['arg1', 'arg2']];

        $this->mockClient
            ->expects($this->once())
            ->method('createCommand')
            ->with($this->equalTo('test'), $modifiedArguments)
            ->willReturn($this->mockCommand);

        $this->mockClient
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->mockCommand)
            ->willReturn($this->expectedValue);

        $this->assertSame($this->expectedValue, $this->testClass->test($this->arguments));
    }
}
