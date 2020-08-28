<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
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
        $arguments = array('pattern:*');
        $expected = array('pattern:*');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('key1', 'key2', 'key3');
        $parsed = array('key1', 'key2', 'key3');

        $this->assertSame($parsed, $this->getCommand()->parseResponse($raw));
    }

    /**
     * @group connected
     */
    public function testReturnsArrayOfMatchingKeys(): void
    {
        $keys = array('aaa' => 1, 'aba' => 2, 'aca' => 3);
        $keysNS = array('metavar:foo' => 'bar', 'metavar:hoge' => 'piyo');
        $keysAll = array_merge($keys, $keysNS);

        $redis = $this->getClient();
        $redis->mset($keysAll);

        $this->assertSame(array(), $redis->keys('nomatch:*'));
        $this->assertSameValues(array_keys($keysNS), $redis->keys('metavar:*'));
        $this->assertSameValues(array_keys($keysAll), $redis->keys('*'));
        $this->assertSameValues(array_keys($keys), $redis->keys('a?a'));
    }
}
