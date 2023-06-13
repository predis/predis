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

use Predis\Command\Redis;

class SPUBLISH_Test extends Redis\PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return SPUBLISH::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'SPUBLISH';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['channel', 'message'];
        $expected = ['channel', 'message'];

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
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.0.0
     */
    public function testPublishesMessagesToChannel(): void
    {
        $redis1 = $this->getClient();
        $redis2 = $this->getClient();

        $redis1->ssubscribe('channel:foo');

        $this->assertSame(1, $redis2->spublish('channel:foo', 'bar'));
        $this->assertSame(0, $redis2->spublish('channel:hoge', 'piyo'));
    }
}
