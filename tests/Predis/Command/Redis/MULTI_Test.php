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
        $command->setArguments([]);

        $this->assertSame([], $command->getArguments());
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
     * @group relay-incompatible
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
     * @group ext-relay
     */
    public function testInitializesNewTransactionUsingRelay(): void
    {
        $redis = $this->getClient();
        $relay = $redis->getConnection()->getClient();

        $this->assertSame($relay, $redis->multi());
        $this->assertSame($relay, $redis->echo('tx1'));
        $this->assertSame($relay, $redis->echo('tx2'));

        $relay->discard();
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @group relay-fixme
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
     * @group relay-incompatible
     * @group relay-fixme
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
