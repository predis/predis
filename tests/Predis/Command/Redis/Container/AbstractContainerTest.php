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

use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;

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
            public function getContainerCommandId(): string
            {
                return 'test';
            }
        };
    }

    /**
     * @return void
     */
    public function testGetContainerId(): void
    {
        $this->assertSame('test', $this->testClass->getContainerCommandId());
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
