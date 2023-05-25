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

class SUNSUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return SUNSUBSCRIBE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'SUNSUBSCRIBE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['channel:foo', 'channel:bar'];
        $expected = ['channel:foo', 'channel:bar'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['sunsubscribe', 'channel', 1];
        $expected = ['sunsubscribe', 'channel', 1];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testUnsubscribesFromGivenShardedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['sunsubscribe', 'channel1', 0], $redis->sunsubscribe('channel1'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testUnsubscribesFromAllSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['ssubscribe', 'channel:foo', 1], $redis->ssubscribe('channel:foo'));
        $this->assertSame(['ssubscribe', 'channel:bar', 2], $redis->ssubscribe('channel:bar'));

        [$_, $unsubscribed1, $_] = $redis->sunsubscribe();
        [$_, $unsubscribed2, $_] = $redis->getConnection()->read();
        $this->assertSameValues(['channel:foo', 'channel:bar'], [$unsubscribed1, $unsubscribed2]);

        $this->assertSame('echoed', $redis->echo('echoed'));
    }
}
