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
class DISCARD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\Redis\DISCARD';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'DISCARD';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $command = $this->getCommand();
        $command->setArguments(array());

        $this->assertSame(array(), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testAbortsTransactionAndRestoresNormalFlow()
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
    public function testThrowsExceptionWhenCallingOutsideTransaction()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR DISCARD without MULTI');

        $redis = $this->getClient();

        $redis->discard();
    }
}
