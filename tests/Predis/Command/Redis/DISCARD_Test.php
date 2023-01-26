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

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-transaction
 */
class DISCARD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\DISCARD';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'DISCARD';
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
     * @requiresRedisVersion >= 2.0.0
     */
    public function testAbortsTransactionAndRestoresNormalFlow(): void
    {
        $redis = $this->getClient();

        $redis->multi();

        $this->assertEquals('QUEUED', $redis->set('foo', 'bar'));
        $this->assertEquals('OK', $redis->discard());
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionWhenCallingOutsideTransaction(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR DISCARD without MULTI');

        $redis = $this->getClient();

        $redis->discard();
    }
}
