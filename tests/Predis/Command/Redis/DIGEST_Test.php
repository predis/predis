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

use Predis\Command\Redis\Utils\CommandUtility;

class DIGEST_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return DIGEST::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'DIGEST';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key1'];
        $expected = ['key1'];

        $command = $this->getCommand();
        $command->setArguments($arguments);
        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group connected
     * @requires PHP >= 8.1
     * @requiresRedisVersion >= 8.3.224
     */
    public function testReturnsDigestOfGivenValue(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertEquals(CommandUtility::xxh3Hash('bar'), $redis->digest('foo'));
    }
}
