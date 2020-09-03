<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-transaction
 */
class MULTI_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MULTI';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MULTI';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(array());

        $this->assertSame(array(), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     */
    public function testInitializesNewTransaction(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->multi());
        $this->assertEquals('QUEUED', $redis->echo('tx1'));
        $this->assertEquals('QUEUED', $redis->echo('tx2'));
    }

    /**
     * @group connected
     */
    public function testActuallyReturnsResponseObjectAbstraction(): void
    {
        $redis = $this->getClient();

        $this->assertInstanceOf('Predis\Response\Status', $redis->multi());
        $this->assertInstanceOf('Predis\Response\Status', $redis->echo('tx1'));
        $this->assertInstanceOf('Predis\Response\Status', $redis->echo('tx2'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionWhenCallingMultiInsideTransaction(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR MULTI calls can not be nested');

        $redis = $this->getClient();

        $redis->multi();
        $redis->multi();
    }
}
