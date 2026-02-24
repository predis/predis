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

class DELEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return DELEX::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'DELEX';
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
    public function testDeleteValueWithDifferentModifiers(): void
    {
        $redis = $this->getClient();
        $this->assertEquals(
            'OK',
            $redis->set('foo', 'bar')
        );

        $value = $redis->get('foo');
        $this->assertEquals(
            1,
            $redis->delex('foo', 'IFEQ', $value)
        );
        $this->assertEquals(
            'OK',
            $redis->set('foo', 'bar')
        );

        $this->assertEquals(
            0,
            $redis->delex('foo', 'IFEQ', 'wrong')
        );
        $this->assertEquals(
            1,
            $redis->delex('foo', 'IFNE', 'not equal')
        );
        $this->assertEquals(
            'OK',
            $redis->set('foo', 'bar')
        );

        $this->assertEquals(
            0,
            $redis->delex('foo', 'IFNE', $value)
        );
        $this->assertEquals(
            1,
            $redis->delex('foo', 'IFDEQ', CommandUtility::xxh3Hash($value))
        );
        $this->assertEquals(
            'OK',
            $redis->set('foo', 'bar')
        );

        $this->assertEquals(
            0,
            $redis->delex('foo', 'IFDEQ', CommandUtility::xxh3Hash('wrong'))
        );
        $this->assertEquals(
            1,
            $redis->delex('foo', 'IFDNE', CommandUtility::xxh3Hash('not equal'))
        );
        $this->assertEquals(
            'OK',
            $redis->set('foo', 'bar')
        );

        $this->assertEquals(
            0,
            $redis->delex('foo', 'IFDNE', CommandUtility::xxh3Hash($value))
        );
    }
}
