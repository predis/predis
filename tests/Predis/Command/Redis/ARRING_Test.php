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
class ARRING_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARRING::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARRING';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 3, 'a', 'b', 'c'];
        $expected = ['key', 3, 'a', 'b', 'c'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsValuesAsSingleArray(): void
    {
        $arguments = ['key', 3, ['a', 'b', 'c']];
        $expected = ['key', 3, 'a', 'b', 'c'];

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
        $actualArguments = ['arg1', 3, 'a'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 3, 'a'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testInsertsValuesSequentiallyStartingAtZero(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->arring('arr', 3, 'a'));
        $this->assertSame('a', $redis->arget('arr', 0));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testInsertsMultipleValuesInSingleCall(): void
    {
        $redis = $this->getClient();

        $this->assertSame(2, $redis->arring('arr', 3, 'a', 'b', 'c'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testWrapsAroundOnceRingIsFull(): void
    {
        $redis = $this->getClient();

        $redis->arring('arr', 3, 'a', 'b', 'c');

        $this->assertSame(0, $redis->arring('arr', 3, 'd'));
        $this->assertSame('d', $redis->arget('arr', 0));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testInsertsValuesResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame(2, $redis->arring('arr', 3, 'a', 'b', 'c'));
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
        $redis->arring('foo', 3, 'a');
    }
}
