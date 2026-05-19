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
class ARINSERT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARINSERT::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARINSERT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 'value1', 'value2', 'value3'];
        $expected = ['key', 'value1', 'value2', 'value3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsValuesAsSingleArray(): void
    {
        $arguments = ['key', ['value1', 'value2', 'value3']];
        $expected = ['key', 'value1', 'value2', 'value3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(2, $this->getCommand()->parseResponse(2));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 'a', 'b'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'a', 'b'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testInsertsMultipleValues(): void
    {
        $redis = $this->getClient();

        $this->assertSame(2, $redis->arinsert('arr', 'a', 'b', 'c'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testCursorAdvancesAfterInsert(): void
    {
        $redis = $this->getClient();

        $redis->arinsert('arr', 'a', 'b', 'c');

        $this->assertSame(3, $redis->arnext('arr'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testInsertsValuesResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame(2, $redis->arinsert('arr', 'a', 'b', 'c'));
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
        $redis->arinsert('foo', 'baz');
    }
}
