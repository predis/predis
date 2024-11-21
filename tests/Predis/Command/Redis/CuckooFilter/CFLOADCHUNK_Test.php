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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CFLOADCHUNK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFLOADCHUNK::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFLOADCHUNK';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 1, 'data'];
        $expectedArguments = ['key', 1, 'data'];

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
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testLoadChunkSuccessfullyRestoresCuckooFilter(): void
    {
        $redis = $this->getClient();

        $redis->cfadd('key', 'item1');

        $chunks = [];
        $iter = 0;

        while (true) {
            [$iter, $data] = $redis->cfscandump('key', $iter);

            if ($iter === 0) {
                break;
            }

            $chunks[] = [$iter, $data];
        }

        $redis->flushall();

        foreach ($chunks as $chunk) {
            [$iter, $data] = $chunk;
            $actualResponse = $redis->cfloadchunk('key', $iter, $data);

            $this->assertEquals('OK', $actualResponse);
        }

        $this->assertSame(1, $redis->cfexists('key', 'item1'));
    }

    /**
     * @group connected
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Invalid position');

        $redis = $this->getClient();

        $redis->set('cfloadchunk_foo', 'bar');
        $redis->cfloadchunk('cfloadchunk_foo', 0, 'data');
    }
}
