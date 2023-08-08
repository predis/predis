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
class TDIGESTADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TDIGESTADD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TDIGESTADD';
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
    public function testAddObservationsIntoGivenTDigestSketch(): void
    {
        $redis = $this->getClient();

        $redis->tdigestcreate('key');

        $actualResponse = $redis->tdigestadd('key', 1, 2, 3);
        $info = $redis->tdigestinfo('key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(3, $info['Observations']);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testAddObservationsIntoGivenTDigestSketchResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->tdigestcreate('key');

        $actualResponse = $redis->tdigestadd('key', 1, 2, 3);
        $info = $redis->tdigestinfo('key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(3, $info['Observations']);
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

        $redis->tdigestadd('key', 1, 2, 3);
    }
}
