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

class SADDEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return SADDEX::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'SADDEX';
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
     * @dataProvider unexpectedValuesProvider
     */
    public function testFilterArgumentsThrowsExceptionOnUnexpectedValueGiven(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $command = $this->getCommand();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $command->setArguments($arguments);
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $command->setArguments(['key', ['member1', 'member2'], SADDEX::COND_NULL, SADDEX::TTL_EX, 10]);
        $command->prefixKeys('prefix:');

        $this->assertSame(['prefix:key', 'EX', 10, 'MEMBERS', 2, 'member1', 'member2'], $command->getArguments());
    }

    /**
     * @group connected
     * @group slow
     * @dataProvider setProvider
     * @requiresRedisVersion >= 8.11.0
     */
    public function testAddsMembersWithExpiration(
        string $condition,
        string $ttlModifier,
        int $ttlValue,
        float $timeout
    ): void {
        $redis = $this->getClient();

        // Absolute timestamps must be computed at run time, not when the data provider is built
        if ($ttlModifier === SADDEX::TTL_EXAT) {
            $ttlValue += time();
        } elseif ($ttlModifier === SADDEX::TTL_PXAT) {
            $ttlValue += (int) (microtime(true) * 1000);
        }

        $this->assertSame(1, $redis->saddex('setkey', ['member1', 'member2'], $condition, $ttlModifier, $ttlValue));
        $this->assertSameValues(['member1', 'member2'], $redis->smembers('setkey'));

        $this->sleep($timeout);

        $this->assertSame([], $redis->smembers('setkey'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testAddsMembersOnlyIfNoneExists(): void
    {
        $redis = $this->getClient();

        $this->assertSame(1, $redis->saddex('setkey', ['member1', 'member2'], SADDEX::COND_MNX));
        $this->assertSame(0, $redis->saddex('setkey', ['member2', 'member3'], SADDEX::COND_MNX));
        $this->assertSameValues(['member1', 'member2'], $redis->smembers('setkey'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testUpdatesMembersOnlyIfAllExist(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->saddex('setkey', ['member1'], SADDEX::COND_MXX));
        $this->assertSame(1, $redis->saddex('setkey', ['member1', 'member2']));
        $this->assertSame(0, $redis->saddex('setkey', ['member1', 'member3'], SADDEX::COND_MXX, SADDEX::TTL_EX, 100));
        $this->assertSame(1, $redis->saddex('setkey', ['member1', 'member2'], SADDEX::COND_MXX, SADDEX::TTL_EX, 100));
        $this->assertSame([1, 1], $redis->sexpire('setkey', 100, ['member1', 'member2'], 'XX'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testKeepsOrDiscardsExistingTTL(): void
    {
        $redis = $this->getClient();

        $redis->saddex('setkey', ['member1', 'member2'], SADDEX::COND_NULL, SADDEX::TTL_EX, 100);

        // Existing member with KEEPTTL: nothing changes, TTL is kept
        $this->assertSame(0, $redis->saddex('setkey', ['member1'], SADDEX::COND_NULL, SADDEX::TTL_KEEP_TTL));
        $this->assertSame([1], $redis->sexpire('setkey', 100, ['member1'], 'XX'));

        // Existing member without KEEPTTL: TTL is discarded
        $this->assertSame(1, $redis->saddex('setkey', ['member2']));
        $this->assertSame([0], $redis->sexpire('setkey', 100, ['member2'], 'XX'));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', ['member1', 'member2']],
                ['key', 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with MNX condition' => [
                ['key', ['member1', 'member2'], SADDEX::COND_MNX],
                ['key', 'MNX', 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with MXX condition' => [
                ['key', ['member1', 'member2'], SADDEX::COND_MXX],
                ['key', 'MXX', 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with uppercase condition' => [
                ['key', ['member1'], 'MNX'],
                ['key', 'MNX', 'MEMBERS', 1, 'member1'],
            ],
            'with EX modifier' => [
                ['key', ['member1', 'member2'], SADDEX::COND_NULL, SADDEX::TTL_EX, 10],
                ['key', 'EX', 10, 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with PX modifier' => [
                ['key', ['member1', 'member2'], SADDEX::COND_NULL, SADDEX::TTL_PX, 10],
                ['key', 'PX', 10, 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with EXAT modifier' => [
                ['key', ['member1', 'member2'], SADDEX::COND_NULL, SADDEX::TTL_EXAT, 10],
                ['key', 'EXAT', 10, 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with PXAT modifier' => [
                ['key', ['member1', 'member2'], SADDEX::COND_NULL, SADDEX::TTL_PXAT, 10],
                ['key', 'PXAT', 10, 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with KEEPTTL modifier' => [
                ['key', ['member1', 'member2'], SADDEX::COND_NULL, SADDEX::TTL_KEEP_TTL],
                ['key', 'KEEPTTL', 'MEMBERS', 2, 'member1', 'member2'],
            ],
            'with combined modifiers' => [
                ['key', ['member1', 'member2'], SADDEX::COND_MXX, SADDEX::TTL_PXAT, 10],
                ['key', 'MXX', 'PXAT', 10, 'MEMBERS', 2, 'member1', 'member2'],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong condition' => [
                ['key', ['member1'], 'wrong'],
                'Condition argument accepts only: mnx, mxx values',
            ],
            'with wrong TTL modifier' => [
                ['key', ['member1'], SADDEX::COND_NULL, 'wrong'],
                'Modifier argument accepts only: ex, px, exat, pxat, keepttl values',
            ],
            'with missing TTL modifier value' => [
                ['key', ['member1'], SADDEX::COND_NULL, SADDEX::TTL_EX],
                'Modifier value is missing or incorrect type',
            ],
            'with wrong TTL modifier value type' => [
                ['key', ['member1'], SADDEX::COND_NULL, SADDEX::TTL_EX, '10'],
                'Modifier value is missing or incorrect type',
            ],
        ];
    }

    public function setProvider(): array
    {
        return [
            'with EX modifier' => [SADDEX::COND_NULL, SADDEX::TTL_EX, 1, 1.2],
            'with PX modifier' => [SADDEX::COND_NULL, SADDEX::TTL_PX, 100, 0.2],
            'with EXAT modifier' => [SADDEX::COND_NULL, SADDEX::TTL_EXAT, 1, 2],
            'with PXAT modifier' => [SADDEX::COND_NULL, SADDEX::TTL_PXAT, 100, 0.3],
            'with combined modifiers' => [SADDEX::COND_MNX, SADDEX::TTL_PX, 100, 0.2],
        ];
    }
}
