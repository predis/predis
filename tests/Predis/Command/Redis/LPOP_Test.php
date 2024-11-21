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
 * @group realm-list
 */
class LPOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LPOP';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LPOP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key'];
        $expected = ['key'];

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
    public function testPopsTheFirstElementFromList(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd');

        $this->assertSame('a', $redis->lpop('letters'));
        $this->assertSame('b', $redis->lpop('letters'));
        $this->assertSame(['c', 'd'], $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testReturnsNullOnEmptyList(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->lpop('letters'));
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
        $redis->lpop('foo');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2
     */
    public function testPopsSpecifiedNumberOfElements(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f');

        $this->assertSame(['a', 'b'], $redis->lpop('letters', 2));
        $this->assertSame(['c', 'd'], $redis->lpop('letters', 2));
        $this->assertSame(['e', 'f'], $redis->lrange('letters', 0, -1));
    }
}
