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
class TDIGESTINFO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TDIGESTINFO::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TDIGESTINFO';
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
        $actualResponse = [
            'Compression', 100, 'Capacity', 610, 'Merged nodes', 0, 'Unmerged nodes', 5, 'Merged weight', 0,
            'Unmerged weight', 5, 'Observations', 5, 'Total compressions', 0, 'Memory usage', 9768,
        ];
        $expectedResponse = [
            'Compression' => 100,
            'Capacity' => 610,
            'Merged nodes' => 0,
            'Unmerged nodes' => 5,
            'Merged weight' => 0,
            'Unmerged weight' => 5,
            'Observations' => 5,
            'Total compressions' => 0,
            'Memory usage' => 9768,
        ];

        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.4.0
     */
    public function testInfoReturnsInformationAboutGivenTDigestSketch(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [
            'Compression' => 100,
            'Capacity' => 610,
            'Merged nodes' => 0,
            'Unmerged nodes' => 0,
            'Merged weight' => 0,
            'Unmerged weight' => 0,
            'Observations' => 0,
            'Total compressions' => 0,
            'Memory usage' => 9768,
        ];

        $redis->tdigestcreate('key');

        $this->assertSame($expectedResponse, $redis->tdigestinfo('key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testInfoReturnsInformationAboutGivenTDigestSketchResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = [
            'Compression' => 100,
            'Capacity' => 610,
            'Merged nodes' => 0,
            'Unmerged nodes' => 0,
            'Merged weight' => 0,
            'Unmerged weight' => 0,
            'Observations' => 0,
            'Total compressions' => 0,
            'Memory usage' => 9768,
        ];

        $redis->tdigestcreate('key');

        $this->assertSame($expectedResponse, $redis->tdigestinfo('key'));
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

        $redis->tdigestinfo('key');
    }
}
