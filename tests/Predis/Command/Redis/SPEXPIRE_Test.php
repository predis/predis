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

class SPEXPIRE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return SPEXPIRE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'SPEXPIRE';
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

        $command->setArguments(['key', 1000, ['member'], 'wrong']);
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
        $command->setArguments(['key', 100, ['member1', 'member2']]);
        $command->prefixKeys('prefix:');

        $this->assertSame(['prefix:key', 100, 'MEMBERS', 2, 'member1', 'member2'], $command->getArguments());
    }

    /**
     * @dataProvider setProvider
     * @group connected
     * @group slow
     * @requiresRedisVersion >= 8.11.0
     */
    public function testSetMembersExpireCorrectlyWithNoFlags(
        array $members,
        array $expireArgs,
        array $expectedResponse,
        array $expectedMembers
    ): void {
        $redis = $this->getClient();

        $redis->saddex('setkey', $members);

        $this->assertSame($expectedResponse, $redis->spexpire(...$expireArgs));
        $this->sleep(0.2);
        $this->assertSameValues($expectedMembers, $redis->smembers('setkey'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testSetMembersExpireCorrectlyWithFlags(): void
    {
        $redis = $this->getClient();

        $redis->saddex('setkey', ['member1', 'member2']);

        $this->assertSame([1, 1], $redis->spexpire('setkey', 20000, ['member1', 'member2']));
        $this->assertSame([0, 0], $redis->spexpire('setkey', 20000, ['member1', 'member2'], 'NX'));
        $this->assertSame([1, 1], $redis->spexpire('setkey', 20000, ['member1', 'member2'], 'XX'));
        $this->assertSame([1, 1], $redis->spexpire('setkey', 30000, ['member1', 'member2'], 'GT'));
        $this->assertSame([0, 0], $redis->spexpire('setkey', 10000, ['member1', 'member2'], 'GT'));
        $this->assertSame([1, 1], $redis->spexpire('setkey', 20000, ['member1', 'member2'], 'LT'));
        $this->assertSame([0, 0], $redis->spexpire('setkey', 30000, ['member1', 'member2'], 'LT'));
        $this->assertSame([-2, -2], $redis->spexpire('wrongkey', 20000, ['member1', 'member2']));
    }

    public function setProvider(): array
    {
        return [
            'with all members expired' => [
                ['member1', 'member2'],
                ['setkey', 100, ['member1', 'member2']],
                [1, 1],
                [],
            ],
            'with partial members expired' => [
                ['member1', 'member2'],
                ['setkey', 100, ['member1']],
                [1],
                ['member2'],
            ],
            'with incorrect members' => [
                ['member1', 'member2'],
                ['setkey', 100, ['member3', 'member4']],
                [-2, -2],
                ['member1', 'member2'],
            ],
        ];
    }

    public function argumentsProvider(): array
    {
        return [
            'with specified members' => [
                ['key', 100, ['member1', 'member2']],
                ['key', 100, 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with lowercase flag' => [
                ['key', 100, ['member1'], 'nx'],
                ['key', 100, 'NX', 'MEMBERS', 1, 'member1'],
            ],
            'with all arguments' => [
                ['key', 100, ['member1', 'member2'], 'XX'],
                ['key', 100, 'XX', 'MEMBERS', 2, 'member1', 'member2'],
            ],
        ];
    }
}
