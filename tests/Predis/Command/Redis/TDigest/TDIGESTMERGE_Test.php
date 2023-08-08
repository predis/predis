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

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TDIGESTMERGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TDIGESTMERGE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TDIGESTMERGE';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @dataProvider sketchesProvider
     * @param  string $sourceKey1
     * @param  string $sourceKey2
     * @param  array  $sourceKey1AddValues
     * @param  array  $sourceKey2AddValues
     * @param  array  $mergeValues
     * @param  int    $expectedCompression
     * @param  array  $expectedMergedSketchValues
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testMergeTwoSketchesIntoOneWithinGivenDestinationKey(
        string $sourceKey1,
        string $sourceKey2,
        array $sourceKey1AddValues,
        array $sourceKey2AddValues,
        array $mergeValues,
        int $expectedCompression,
        array $expectedMergedSketchValues
    ): void {
        $redis = $this->getClient();

        $redis->tdigestcreate($sourceKey1);
        $redis->tdigestcreate($sourceKey2);

        $redis->tdigestadd(...$sourceKey1AddValues);
        $redis->tdigestadd(...$sourceKey2AddValues);

        $actualResponse = $redis->tdigestmerge(...$mergeValues);
        $info = $redis->tdigestinfo('destination-key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame($expectedCompression, $info['Compression']);
        $this->assertEquals(
            $expectedMergedSketchValues,
            $redis->tdigestbyrank('destination-key', 0, 1, 2, 3, 4)
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testMergedSketchHaveCompressionEqualMaxValueAmongAllSourceSketches(): void
    {
        $redis = $this->getClient();

        $redis->tdigestcreate('source-key1');
        $redis->tdigestcreate('source-key2', 1000);

        $redis->tdigestadd('source-key1', 1, 2);
        $redis->tdigestadd('source-key2', 3, 4);

        $actualResponse = $redis->tdigestmerge('destination-key', ['source-key1', 'source-key2']);
        $info = $redis->tdigestinfo('destination-key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(1000, $info['Compression']);
        $this->assertEquals(
            ['1', '2', '3', '4', 'inf'],
            $redis->tdigestbyrank('destination-key', 0, 1, 2, 3, 4)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testMergedSketchHaveCompressionEqualMaxValueAmongAllSourceSketchesResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->tdigestcreate('source-key1');
        $redis->tdigestcreate('source-key2', 1000);

        $redis->tdigestadd('source-key1', 1, 2);
        $redis->tdigestadd('source-key2', 3, 4);

        $actualResponse = $redis->tdigestmerge('destination-key', ['source-key1', 'source-key2']);
        $info = $redis->tdigestinfo('destination-key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(1000, $info['Compression']);
        $this->assertEquals(
            [1.0, 2.0, 3.0, 4.0, INF],
            $redis->tdigestbyrank('destination-key', 0, 1, 2, 3, 4)
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testMergeOverrideAlreadyExistingSketchWithOverrideModifier(): void
    {
        $redis = $this->getClient();

        $redis->tdigestcreate('source-key1');
        $redis->tdigestcreate('source-key2');
        $redis->tdigestcreate('destination-key');

        $redis->tdigestadd('source-key1', 1, 2);
        $redis->tdigestadd('source-key2', 3, 4);
        $redis->tdigestadd('destination-key', 5, 6, 7, 8);

        $this->assertEquals(
            ['5', '6', '7', '8', 'inf'],
            $redis->tdigestbyrank('destination-key', 0, 1, 2, 3, 4)
        );

        $actualResponse = $redis->tdigestmerge(
            'destination-key',
            ['source-key1', 'source-key2'],
            0,
            true
        );
        $info = $redis->tdigestinfo('destination-key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(100, $info['Compression']);
        $this->assertEquals(
            ['1', '2', '3', '4', 'inf'],
            $redis->tdigestbyrank('destination-key', 0, 1, 2, 3, 4)
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testMergeWithAlreadyExistingSketchIfNoOverrideModifierGiven(): void
    {
        $redis = $this->getClient();

        $redis->tdigestcreate('source-key1');
        $redis->tdigestcreate('source-key2');
        $redis->tdigestcreate('destination-key');

        $redis->tdigestadd('source-key1', 1, 2);
        $redis->tdigestadd('source-key2', 3, 4);
        $redis->tdigestadd('destination-key', 5, 6, 7, 8);

        $actualResponse = $redis->tdigestmerge('destination-key', ['source-key1', 'source-key2']);
        $info = $redis->tdigestinfo('destination-key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(100, $info['Compression']);
        $this->assertEquals(
            ['1', '2', '3', '4', '5', '6', '7', '8', 'inf'],
            $redis->tdigestbyrank('destination-key', 0, 1, 2, 3, 4, 5, 6, 7, 8)
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testThrowsExceptionOnNonExistingSourceKeyGiven(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR T-Digest: key does not exist');

        $redis->tdigestmerge('destination-key', ['source-key1', 'source-key2']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', ['source-key1', 'source-key2']],
                ['key', 2, 'source-key1', 'source-key2'],
            ],
            'with COMPRESSION argument' => [
                ['key', ['source-key1', 'source-key2'], 10],
                ['key', 2, 'source-key1', 'source-key2', 'COMPRESSION', 10],
            ],
            'with OVERRIDE modifier' => [
                ['key', ['source-key1', 'source-key2'], 0, true],
                ['key', 2, 'source-key1', 'source-key2', 'OVERRIDE'],
            ],
            'with all arguments' => [
                ['key', ['source-key1', 'source-key2'], 10, true],
                ['key', 2, 'source-key1', 'source-key2', 'COMPRESSION', 10, 'OVERRIDE'],
            ],
        ];
    }

    public function sketchesProvider(): array
    {
        return [
            'with default arguments' => [
                'source-key1',
                'source-key2',
                ['source-key1', 1, 2],
                ['source-key2', 3, 4],
                ['destination-key', ['source-key1', 'source-key2']],
                100,
                ['1', '2', '3', '4', 'inf'],
            ],
            'with non-default COMPRESSION' => [
                'source-key1',
                'source-key2',
                ['source-key1', 1, 2],
                ['source-key2', 3, 4],
                ['destination-key', ['source-key1', 'source-key2'], 200],
                200,
                ['1', '2', '3', '4', 'inf'],
            ],
            'with OVERRIDE modifier' => [
                'source-key1',
                'source-key2',
                ['source-key1', 1, 2],
                ['source-key2', 3, 4],
                ['destination-key', ['source-key1', 'source-key2'], 0, true],
                100,
                ['1', '2', '3', '4', 'inf'],
            ],
        ];
    }
}
