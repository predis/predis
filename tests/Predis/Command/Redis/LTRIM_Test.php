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
 * @group realm-list
 */
class LTRIM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LTRIM';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LTRIM';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, 1];
        $expected = ['key', 0, 1];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
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
    public function testTrimsListWithPositiveStartAndStop(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertEquals('OK', $redis->ltrim('letters', 0, 2));
        $this->assertSame(['a', 'b', 'c'], $redis->lrange('letters', 0, -1));

        $redis->flushdb();
        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertEquals('OK', $redis->ltrim('letters', 5, 9));
        $this->assertSame(['f', 'g', 'h', 'i', 'l'], $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testTrimsListWithPositiveStartAndNegativeStop(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertEquals('OK', $redis->ltrim('letters', 0, -6));
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testTrimsListWithNegativeStartAndStop(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertEquals('OK', $redis->ltrim('letters', -5, -5));
        $this->assertSame(['f'], $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testHandlesStartAndStopOverflow(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertEquals('OK', $redis->ltrim('letters', -100, 100));
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l'], $redis->lrange('letters', -100, 100));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->ltrim('metavars', 0, 1);
    }
}
