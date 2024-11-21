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
 * @group realm-hash
 */
class SMISMEMBER_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return SMISMEMBER::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SMISMEMBER';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 'member1', 'member2'];
        $expected = ['key', 'member1', 'member2'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group connected
     * @dataProvider membersProvider
     * @param  array  $set
     * @param  string $key
     * @param  array  $members
     * @param  array  $expectedResponse
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsCorrectResponseIfMemberBelongsToSet(
        array $set,
        string $key,
        array $members,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->sadd(...$set);

        $this->assertSame($expectedResponse, $redis->smismember($key, ...$members));
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
        $redis->sismember('foo', 'member1');
    }

    public function membersProvider(): array
    {
        return [
            'with one member - belongs to set' => [
                ['key', 'member1'],
                'key',
                ['member1'],
                [1],
            ],
            'with one member - does not belongs to set' => [
                ['key', 'member1'],
                'key',
                ['member2'],
                [0],
            ],
            'with multiple members - belongs to set' => [
                ['key', 'member1', 'member2'],
                'key',
                ['member1', 'member2'],
                [1, 1],
            ],
            'with multiple members - partially belongs to set' => [
                ['key', 'member1', 'member2'],
                'key',
                ['member1', 'member3'],
                [1, 0],
            ],
            'with multiple members - does not belongs to set' => [
                ['key', 'member1', 'member2'],
                'key',
                ['member3', 'member4'],
                [0, 0],
            ],
        ];
    }
}
