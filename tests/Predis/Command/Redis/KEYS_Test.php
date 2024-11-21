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

/**
 * @group commands
 * @group realm-key
 */
class KEYS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\KEYS';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'KEYS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['pattern:*'];
        $expected = ['pattern:*'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['key1', 'key2', 'key3'];
        $parsed = ['key1', 'key2', 'key3'];

        $this->assertSame($parsed, $this->getCommand()->parseResponse($raw));
    }

    /**
     * @group connected
     */
    public function testReturnsArrayOfMatchingKeys(): void
    {
        $keys = ['aaa' => 1, 'aba' => 2, 'aca' => 3];
        $keysNS = ['metavar:foo' => 'bar', 'metavar:hoge' => 'piyo'];
        $keysAll = array_merge($keys, $keysNS);

        $redis = $this->getClient();
        $redis->mset($keysAll);

        $this->assertSame([], $redis->keys('nomatch:*'));
        $this->assertSameValues(array_keys($keysNS), $redis->keys('metavar:*'));
        $this->assertSameValues(array_keys($keysAll), $redis->keys('*'));
        $this->assertSameValues(array_keys($keys), $redis->keys('a?a'));
    }
}
