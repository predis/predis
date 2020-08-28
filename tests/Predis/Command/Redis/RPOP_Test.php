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
 * @group realm-list
 */
class RPOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\RPOP';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'RPOP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key');
        $expected = array('key');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('element', $this->getCommand()->parseResponse('element'));
    }

    /**
     * @group connected
     */
    public function testPopsTheLastElementFromList(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd');

        $this->assertSame('d', $redis->rpop('letters'));
        $this->assertSame('c', $redis->rpop('letters'));
        $this->assertSame(array('a', 'b'), $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testReturnsNullOnEmptyList(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->rpop('letters'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->rpop('foo');
    }
}
