<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;
use UnexpectedValueException;

class SPEXPIREAT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return SPEXPIREAT::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'SPEXPIREAT';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsThrowsExceptionOnIncorrectFlagValue(): void
    {
        $command = $this->getCommand();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported flag value');

        $command->setArguments(['key', 1893456000000, ['member'], 'wrong']);
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame([1, -2], $command->parseResponse([1, -2]));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $command->setArguments(['key', 1893456000000, ['member1', 'member2']]);
        $command->prefixKeys('prefix:');

        $this->assertSame(['prefix:key', 1893456000000, 'MEMBERS', 2, 'member1', 'member2'], $command->getArguments());
    }

    /**
     * @dataProvider setProvider
     * @group connected
     * @group slow
     * @requiresRedisVersion >= 8.11.0
     */
    public function testSetMembersExpireCorrectlyWithNoFlags(
        array $members,
        array $expireMembers,
        array $expectedResponse,
        array $expectedMembers
    ): void {
        $redis = $this->getClient();

        $redis->saddex('setkey', $members);

        $this->assertSame($expectedResponse, $redis->spexpireat('setkey', $this->nowMs() + 100, $expireMembers));
        $this->sleep(0.2);
        $this->assertSameValues($expectedMembers, $redis->smembers('setkey'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testPastTimestampDeletesMembers(): void
    {
        $redis = $this->getClient();

        $redis->saddex('setkey', ['member1', 'member2']);

        $this->assertSame([2], $redis->spexpireat('setkey', $this->nowMs() - 10000, ['member1']));
        $this->assertSame(['member2'], $redis->smembers('setkey'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testSetMembersExpireCorrectlyWithFlags(): void
    {
        $redis = $this->getClient();
        $now = $this->nowMs();

        $redis->saddex('setkey', ['member1', 'member2']);

        $this->assertSame([1, 1], $redis->spexpireat('setkey', $now + 20000, ['member1', 'member2']));
        $this->assertSame([0, 0], $redis->spexpireat('setkey', $now + 20000, ['member1', 'member2'], 'NX'));
        $this->assertSame([1, 1], $redis->spexpireat('setkey', $now + 20000, ['member1', 'member2'], 'XX'));
        $this->assertSame([1, 1], $redis->spexpireat('setkey', $now + 30000, ['member1', 'member2'], 'GT'));
        $this->assertSame([0, 0], $redis->spexpireat('setkey', $now + 10000, ['member1', 'member2'], 'GT'));
        $this->assertSame([1, 1], $redis->spexpireat('setkey', $now + 20000, ['member1', 'member2'], 'LT'));
        $this->assertSame([0, 0], $redis->spexpireat('setkey', $now + 30000, ['member1', 'member2'], 'LT'));
        $this->assertSame([-2, -2], $redis->spexpireat('wrongkey', $now + 20000, ['member1', 'member2']));
    }

    public function setProvider(): array
    {
        return [
            'with all members expired' => [
                ['member1', 'member2'],
                ['member1', 'member2'],
                [1, 1],
                [],
            ],
            'with partial members expired' => [
                ['member1', 'member2'],
                ['member1'],
                [1],
                ['member2'],
            ],
            'with incorrect members' => [
                ['member1', 'member2'],
                ['member3', 'member4'],
                [-2, -2],
                ['member1', 'member2'],
            ],
        ];
    }

    public function argumentsProvider(): array
    {
        return [
            'with specified members' => [
                ['key', 1893456000000, ['member1', 'member2']],
                ['key', 1893456000000, 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with lowercase flag' => [
                ['key', 1893456000000, ['member1'], 'nx'],
                ['key', 1893456000000, 'NX', 'MEMBERS', 1, 'member1'],
            ],
            'with all arguments' => [
                ['key', 1893456000000, ['member1', 'member2'], 'XX'],
                ['key', 1893456000000, 'XX', 'MEMBERS', 2, 'member1', 'member2'],
            ],
        ];
    }

    private function nowMs(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
