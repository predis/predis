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

class VREM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return VREM::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'VREM';
    }

    /**
     * @return void
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key', 'elem']);

        $this->assertSame(['key', 'elem'], $command->getArguments());
    }

    /**
     * @return void
     */
    public function testParseResponse(): void
    {
        $this->assertTrue($this->getCommand()->parseResponse(1));
        $this->assertFalse($this->getCommand()->parseResponse(0));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testRemoveElementFromVectorSet(): void
    {
        $redis = $this->getClient();

        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10)
        );
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem2', 10)
        );
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem3', 10, true)
        );

        $this->assertTrue($redis->vrem('key', 'elem2'));
        $this->assertEquals(2, $redis->vcard('key'));
        $this->assertFalse($redis->vrem('key', 'elem4'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testRemoveElementFromVectorSetResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10)
        );
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem2', 10)
        );
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem3', 10, true)
        );

        $this->assertTrue($redis->vrem('key', 'elem2'));
        $this->assertEquals(2, $redis->vcard('key'));
        $this->assertFalse($redis->vrem('key', 'elem4'));
    }
}
