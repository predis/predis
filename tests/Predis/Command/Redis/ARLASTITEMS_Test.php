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

/**
 * @group commands
 * @group realm-array
 */
class ARLASTITEMS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARLASTITEMS::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARLASTITEMS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 3];
        $expected = ['key', 3];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithReverse(): void
    {
        $arguments = ['key', 3, true];
        $expected = ['key', 3, 'REV'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(['a', 'b', 'c'], $this->getCommand()->parseResponse(['a', 'b', 'c']));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 3];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 3];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsLastInsertedItems(): void
    {
        $redis = $this->getClient();

        $redis->arinsert('arr', 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(['c', 'd', 'e'], $redis->arlastitems('arr', 3));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsLastInsertedItemsInReverse(): void
    {
        $redis = $this->getClient();

        $redis->arinsert('arr', 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(['e', 'd', 'c'], $redis->arlastitems('arr', 3, true));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsAllElementsWhenCountExceedsSize(): void
    {
        $redis = $this->getClient();

        $redis->arinsert('arr', 'a', 'b');

        $this->assertSame(['a', 'b'], $redis->arlastitems('arr', 100));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsLastInsertedItemsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->arinsert('arr', 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(['c', 'd', 'e'], $redis->arlastitems('arr', 3));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->arlastitems('foo', 3);
    }
}
