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

use Predis\Response\ServerException;

class SSUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return SSUBSCRIBE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'SSUBSCRIBE';
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
        $raw = ['ssubscribe', 'channel', 1];
        $expected = ['ssubscribe', 'channel', 1];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testSubscribesToGivenShardedChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['ssubscribe', 'channel1', 1], $redis->ssubscribe('channel1'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testAllowsSUnsubscribeAfterSSubscribe(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['ssubscribe', 'channel1', 1], $redis->ssubscribe('channel1'));
        $this->assertSame(['sunsubscribe', 'channel1', 0], $redis->sunsubscribe('channel1'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testCannotSendOtherCommandsAfterSSubscribe(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessageMatches('/ERR.*only .* allowed in this context/');

        $redis = $this->getClient();

        $redis->ssubscribe('channel:foo');
        $redis->set('foo', 'bar');
    }
}
