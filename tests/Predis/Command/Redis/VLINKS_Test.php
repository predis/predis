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

use Predis\Command\Redis\Utils\VectorUtility;

class VLINKS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return VLINKS::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'VLINKS';
    }

    /**
     * @return void
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key', 'elem']);

        $this->assertSame(['key', 'elem'], $command->getArguments());

        $command->setArguments(['key', 'value', true]);
        $this->assertSame(['key', 'value', 'WITHSCORES'], $command->getArguments());
    }

    /**
     * @return void
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key', 'value']);

        $this->assertEquals([['key', 'value']], $command->parseResponse([['key', 'value']]));

        $command->setArguments(['key', 'value', true]);
        $this->assertEquals([['key' => 1.1]], $command->parseResponse([['key', '1.1']]));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsLinksOfGivenVectorSetElement(): void
    {
        $redis = $this->getClient();

        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1'
            )
        );
        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.2, 0.3, 0.4, 0.5]), 'elem2'
            )
        );
        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.3, 0.4, 0.5, 0.6]), 'elem3'
            )
        );

        // Response is unpredictable given the fact that elements randomly distributed across different HNSW layers
        // We cannot control an amount of nested arrays (HNSW layers) and elements it will contain.
        $this->assertIsArray($redis->vlinks('key', 'elem2'));
        $this->assertIsArray($redis->vlinks('key', 'elem2', true));
        $this->assertNull($redis->vlinks('wrong', 'elem2'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsLinksOfGivenVectorSetElementResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1'
            )
        );
        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.2, 0.3, 0.4, 0.5]), 'elem2'
            )
        );
        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.3, 0.4, 0.5, 0.6]), 'elem3'
            )
        );

        // Response is unpredictable given the fact that elements randomly distributed across different HNSW layers
        // We cannot control an amount of nested arrays (HNSW layers) and elements it will contain.
        $this->assertIsArray($redis->vlinks('key', 'elem2'));
        $this->assertIsArray($redis->vlinks('key', 'elem2', true));
        $this->assertNull($redis->vlinks('wrong', 'elem2'));
    }
}
