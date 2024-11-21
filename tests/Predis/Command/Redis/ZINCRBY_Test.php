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
 * @group realm-zset
 */
class ZINCRBY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZINCRBY';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZINCRBY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 1.0, 'member'];
        $expected = ['key', 1.0, 'member'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('1', $this->getCommand()->parseResponse('1'));
    }

    /**
     * @group connected
     */
    public function testIncrementsScoreOfMemberByFloat(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('1', $redis->zincrby('letters', 1, 'member'));
        $this->assertEquals('0', $redis->zincrby('letters', -1, 'member'));
        $this->assertEquals('0.5', $redis->zincrby('letters', 0.5, 'member'));
        $this->assertEquals('-10', $redis->zincrby('letters', -10.5, 'member'));
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
        $redis->zincrby('foo', 1, 'bar');
    }
}
