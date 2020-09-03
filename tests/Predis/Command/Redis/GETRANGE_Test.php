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
 * @group realm-string
 */
class GETRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\GETRANGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'GETRANGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 5, 10);
        $expected = array('key', 5, 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('substring', $this->getCommand()->parseResponse('substring'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.4.0
     */
    public function testReturnsSubstring(): void
    {
        $redis = $this->getClient();

        $redis->set('string', 'this is a string');

        $this->assertSame('this', $redis->getrange('string', 0, 3));
        $this->assertSame('ing', $redis->getrange('string', -3, -1));
        $this->assertSame('this is a string', $redis->getrange('string', 0, -1));
        $this->assertSame('string', $redis->getrange('string', 10, 100));

        $this->assertSame('t', $redis->getrange('string', 0, 0));
        $this->assertSame('', $redis->getrange('string', -1, 0));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.4.0
     */
    public function testReturnsEmptyStringOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame('', $redis->getrange('string', 0, 3));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.4.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');
        $redis->getrange('metavars', 0, 5);
    }
}
