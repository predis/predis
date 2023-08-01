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
class TDIGESTBYRANK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TDIGESTBYRANK::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TDIGESTBYRANK';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 1, 2, 3];
        $expectedArguments = ['key', 1, 2, 3];

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
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testReturnsValuesEstimatedForGivenRanks(): void
    {
        $redis = $this->getClient();
        $expectedResponse = ['1', '2', '2', '3', '3', '3', 'inf'];

        $redis->tdigestcreate('key');
        $redis->tdigestcreate('empty_key');

        $redis->tdigestadd('key', 1, 2, 2, 3, 3, 3);

        $actualResponse = $redis->tdigestbyrank('key', 0, 1, 2, 3, 4, 5, 6);

        $this->assertEquals($expectedResponse, $actualResponse);
        $this->assertEquals(['nan', 'nan'], $redis->tdigestbyrank('empty_key', 0, 1));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testReturnsValuesEstimatedForGivenRanksResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = [1.0, 2.0, 2.0, 3.0, 3.0, 3.0, INF];

        $redis->tdigestcreate('key');
        $redis->tdigestcreate('empty_key');

        $redis->tdigestadd('key', 1, 2, 2, 3, 3, 3);

        $actualResponse = $redis->tdigestbyrank('key', 0, 1, 2, 3, 4, 5, 6);

        $this->assertEquals($expectedResponse, $actualResponse);
        $this->assertEquals([null, null], $redis->tdigestbyrank('empty_key', 0, 1));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testThrowsExceptionOnNonExistingTDigestSketch(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR T-Digest: key does not exist');

        $redis->tdigestbyrank('key', 1, 2, 3);
    }
}
