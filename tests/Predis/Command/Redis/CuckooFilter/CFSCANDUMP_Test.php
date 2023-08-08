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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CFSCANDUMP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFSCANDUMP::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFSCANDUMP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 1];
        $expectedArguments = ['key', 1];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
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
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testScanDumpReturnsNotEmptyDataChunk(): void
    {
        $expectedIterator = 1;
        $redis = $this->getClient();

        $redis->cfadd('key', 'item1');
        [$iterator, $dataChunk] = $redis->cfscandump('key', 0);

        $this->assertSame($expectedIterator, $iterator);
        $this->assertNotEmpty($dataChunk);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testScanDumpReturnsNotEmptyDataChunkResp3(): void
    {
        $expectedIterator = 1;
        $redis = $this->getResp3Client();

        $redis->cfadd('key', 'item1');
        [$iterator, $dataChunk] = $redis->cfscandump('key', 0);

        $this->assertSame($expectedIterator, $iterator);
        $this->assertNotEmpty($dataChunk);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('cfscandump_foo', 'bar');
        $redis->cfscandump('cfscandump_foo', 0);
    }
}
