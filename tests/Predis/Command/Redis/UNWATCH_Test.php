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
class UNWATCH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\UNWATCH';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'UNWATCH';
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
     * @requiresRedisVersion >= 2.2.0
     */
    public function testUnwatchWatchedKeys(): void
    {
        $redis1 = $this->getClient();
        $redis2 = $this->getClient();

        $redis1->set('foo', 'bar');
        $redis1->watch('foo');
        $this->assertEquals('OK', $redis1->unwatch());
        $redis1->multi();
        $redis1->get('foo');

        $redis2->set('foo', 'hijacked');

        $this->assertSame(array('hijacked'), $redis1->exec());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testCanBeCalledInsideTransaction(): void
    {
        $redis = $this->getClient();

        $redis->multi();
        $this->assertInstanceOf('Predis\Response\Status', $redis->unwatch());
    }
}
