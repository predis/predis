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

class AbstractDispatcherLoopTest extends PredisTestCase
{
    /**
     * @var DispatcherLoopInterface
     */
    private $testClass;

    /**
     * @var MockObject&ConsumerInterface&MockObject
     */
    private $mockConsumer;

    protected function setUp(): void
    {
        parent::setUp();

        $mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->mockConsumer = $this
            ->getMockBuilder(ConsumerInterface::class)
            ->setConstructorArgs([$mockClient])
            ->getMock();

        $this->testClass = new class($this->mockConsumer) extends AbstractDispatcherLoop {
            public function run(): void
            {
                // NOOP
            }

            public function getCallbacks(): array
            {
                return $this->callbacksDictionary;
            }

            public function getDefaultCallback(): callable
            {
                return $this->defaultCallback;
            }
        };
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testGetConsumer(): void
    {
        $this->assertSame($this->mockConsumer, $this->testClass->getConsumer());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetDefaultCallback(): void
    {
        $callback = static function () {
            return 'test';
        };

        $this->testClass->setDefaultCallback($callback);

        $this->assertSame($callback, $this->testClass->getDefaultCallback());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testAttachCallback(): void
    {
        $callback = static function () {
            return 'test';
        };

        $this->testClass->attachCallback('type', $callback);

        $this->assertSame(['type' => $callback], $this->testClass->getCallbacks());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDetachCallback(): void
    {
        $callback = static function () {
            return 'test';
        };

        $this->testClass->attachCallback('type', $callback);

        $this->assertSame(['type' => $callback], $this->testClass->getCallbacks());

        $this->testClass->detachCallback('type');

        $this->assertSame([], $this->testClass->getCallbacks());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testStop(): void
    {
        $this->mockConsumer
            ->expects($this->once())
            ->method('stop')
            ->withAnyParameters();

        $this->testClass->stop();
    }
}
