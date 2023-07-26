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

namespace Predis\Consumer;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\ClientInterface;
use PredisTestCase;

class AbstractConsumerTest extends PredisTestCase
{
    /**
     * @var ConsumerInterface
     */
    private $testClass;

    /**
     * @var MockObject&ClientInterface&MockObject
     */
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();

        $this->testClass = new class($this->mockClient) extends AbstractConsumer {
            protected function getValue()
            {
                return 'payload';
            }
        };
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testStopWithConnectionDrop(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('disconnect')
            ->withAnyParameters();

        $this->assertTrue($this->testClass->stop(true));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testStopWithoutConnectionDrop(): void
    {
        $this->mockClient
            ->expects($this->never())
            ->method('disconnect')
            ->withAnyParameters();

        $this->assertTrue($this->testClass->stop());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testGetClient(): void
    {
        $this->assertSame($this->mockClient, $this->testClass->getClient());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testCurrentReturnsCurrentPayload(): void
    {
        $this->assertSame('payload', $this->testClass->current());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testValidReturnConsumerState(): void
    {
        $this->assertTrue($this->testClass->valid());

        $this->testClass->stop();

        $this->assertFalse($this->testClass->valid());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testKeyReturnsCurrentPosition(): void
    {
        $this->assertSame(0, $this->testClass->key());

        $this->testClass->next();

        $this->assertSame(1, $this->testClass->key());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testNextIncrementPositionOnValidState(): void
    {
        $this->assertSame(0, $this->testClass->key());

        $this->testClass->next();

        $this->assertSame(1, $this->testClass->key());

        $this->testClass->stop();
        $this->testClass->next();

        $this->assertSame(1, $this->testClass->key());
    }
}
