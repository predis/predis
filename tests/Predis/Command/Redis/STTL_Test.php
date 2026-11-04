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

class STTL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return STTL::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'STTL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key', ['member1', 'member2']]);

        $this->assertSame(['key', 'MEMBERS', 2, 'member1', 'member2'], $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame([10, -1, -2], $command->parseResponse([10, -1, -2]));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $command->setArguments(['key', ['member1', 'member2']]);
        $command->prefixKeys('prefix:');

        $this->assertSame(['prefix:key', 'MEMBERS', 2, 'member1', 'member2'], $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testReturnsRemainingTTL(): void
    {
        $redis = $this->getClient();

        $redis->saddex('setkey', ['member1'], SADDEX::COND_NULL, SADDEX::TTL_EX, 10);
        $redis->saddex('setkey', ['member2']);

        $this->assertSame([10, -1, -2], $redis->sttl('setkey', ['member1', 'member2', 'member3']));
        $this->assertSame([-2], $redis->sttl('wrongkey', ['member1']));
    }
}
