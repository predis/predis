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
class TDIGESTMIN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TDIGESTMIN::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TDIGESTMIN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key'];
        $expectedArguments = ['key'];

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
    public function testReturnsMinValueFromGivenSketch(): void
    {
        $redis = $this->getClient();

        $redis->tdigestcreate('key');
        $redis->tdigestcreate('empty_key');

        $redis->tdigestadd('key', 3, 2, 4, 5, 1);

        $actualResponse = $redis->tdigestmin('key');

        $this->assertEquals('1', $actualResponse);
        $this->assertEquals('nan', $redis->tdigestmin('empty_key'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testReturnsMinValueFromGivenSketchResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->tdigestcreate('key');
        $redis->tdigestcreate('empty_key');

        $redis->tdigestadd('key', 3, 2, 4, 5, 1);

        $actualResponse = $redis->tdigestmin('key');

        $this->assertEquals('1', $actualResponse);
        $this->assertEquals(0, $redis->tdigestmin('empty_key'));
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

        $redis->tdigestmin('key');
    }
}
