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

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class BFLOADCHUNK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BFLOADCHUNK::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BFLOADCHUNK';
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
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testLoadChunkSuccessfullyRestoresBloomFilter(): void
    {
        $redis = $this->getClient();

        $redis->bfadd('key', 'item1');

        $chunks = [];
        $iter = 0;

        while (true) {
            [$iter, $data] = $redis->bfscandump('key', $iter);

            if ($iter === 0) {
                break;
            }

            $chunks[] = [$iter, $data];
        }

        $redis->flushall();

        foreach ($chunks as $chunk) {
            [$iter, $data] = $chunk;
            $actualResponse = $redis->bfloadchunk('key', $iter, $data);

            $this->assertEquals('OK', $actualResponse);
        }

        $this->assertSame(1, $redis->bfexists('key', 'item1'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testLoadChunkSuccessfullyRestoresBloomFilterResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->bfadd('key', 'item1');

        $chunks = [];
        $iter = 0;

        while (true) {
            [$iter, $data] = $redis->bfscandump('key', $iter);

            if ($iter === 0) {
                break;
            }

            $chunks[] = [$iter, $data];
        }

        $redis->flushall();

        foreach ($chunks as $chunk) {
            [$iter, $data] = $chunk;
            $actualResponse = $redis->bfloadchunk('key', $iter, $data);

            $this->assertEquals('OK', $actualResponse);
        }

        $this->assertTrue($redis->bfexists('key', 'item1'));
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

        $redis->set('bfloadchunk_foo', 'bar');
        $redis->bfloadchunk('bfloadchunk_foo', 0, 'data');
    }
}
