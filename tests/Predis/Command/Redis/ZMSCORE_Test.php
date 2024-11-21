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

use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-zset
 */
class ZMSCORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZMSCORE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZMSCORE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['zset', 'member1', 'member2', 'member3'];
        $expected = ['zset', 'member1', 'member2', 'member3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @dataProvider membersProvider
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsScoresAssociatedWithMembers(
        string $key,
        array $membersDictionary,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();
        $notExpectedMember = 'not_expected';

        /** @var string[] $members */
        $members = array_filter($membersDictionary, static function ($item) {
            return is_string($item);
        });

        $redis->zadd($key, ...$membersDictionary);

        $this->assertEquals($expectedResponse, $redis->zmscore($key, ...$members));
        $this->assertNull($redis->zmscore($key, $notExpectedMember)[0]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zmscore('foo', '');
    }

    public function membersProvider(): array
    {
        return [['test-zscore', [1, 'member1', 2, 'member2', 3, 'member3'], ['1', '2', '3']]];
    }
}
