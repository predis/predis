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

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TDIGESTRANK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TDIGESTRANK::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TDIGESTRANK';
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
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testReturnsRankOfGivenObservationValue(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [-1, 0, 1, 2, 3];

        $redis->tdigestcreate('key');
        $redis->tdigestcreate('empty_key');

        $redis->tdigestadd('key', 10, 20, 30);

        $actualResponse = $redis->tdigestrank('key', 0, 10, 20, 30, 40);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame([-2, -2, -2], $redis->tdigestrank('empty_key', 1, 2, 3));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testThrowsExceptionOnNonExistingTDigestSketch(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR T-Digest: key does not exist');

        $redis->tdigestrank('key', 1, 2, 3);
    }
}
