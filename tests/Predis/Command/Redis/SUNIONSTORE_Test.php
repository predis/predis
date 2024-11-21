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
 * @group realm-set
 */
class SUNIONSTORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SUNIONSTORE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SUNIONSTORE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key:destination', 'key:source1', 'key:source:2'];
        $expected = ['key:destination', 'key:source1', 'key:source:2'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsSourceKeysAsSingleArray(): void
    {
        $arguments = ['key:destination', ['key:source1', 'key:source:2']];
        $expected = ['key:destination', 'key:source1', 'key:source:2'];

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
     */
    public function testStoresMembersOfSetOnSingleSet(): void
    {
        $redis = $this->getClient();

        $redis->sadd('letters:1st', 'a', 'b', 'c', 'd', 'e', 'f', 'g');

        $this->assertSame(7, $redis->sunionstore('letters:destination', 'letters:1st'));
        $this->assertSameValues(['a', 'b', 'c', 'd', 'e', 'f', 'g'], $redis->smembers('letters:destination'));
    }

    /**
     * @group connected
     */
    public function testStoresUnionOfMultipleSets(): void
    {
        $redis = $this->getClient();

        $redis->sadd('letters:1st', 'b', 'd', 'f');
        $redis->sadd('letters:2nd', 'a', 'c', 'g');
        $redis->sadd('letters:3rd', 'a', 'e', 'f');

        $this->assertSame(5, $redis->sunionstore('letters:destination', 'letters:2nd', 'letters:3rd'));
        $this->assertSameValues(['a', 'c', 'e', 'f', 'g'], $redis->smembers('letters:destination'));

        $this->assertSame(7, $redis->sunionstore('letters:destination', 'letters:1st', 'letters:2nd', 'letters:3rd'));
        $this->assertSameValues(['a', 'b', 'c', 'd', 'e', 'f', 'g'], $redis->smembers('letters:destination'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongTypeOfSourceKey(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('set:source', 'foo');
        $redis->sunionstore('set:destination', 'set:source');
    }
}
