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

class VSETATTR_Test extends PredisCommandTestCase
{
    /**
     * @return string
     */
    protected function getExpectedCommand(): string
    {
        return VSETATTR::class;
    }

    /**
     * @return string
     */
    protected function getExpectedId(): string
    {
        return 'VSETATTR';
    }

    /**
     * @return void
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        // as assoc array
        $command->setArguments(['key', 'elem', ['key1' => 'value1', 'key2' => 'value2']]);

        $this->assertSame(['key', 'elem', '{"key1":"value1","key2":"value2"}'], $command->getArguments());

        // as json
        $command->setArguments(['key', 'elem', '{"key1":"value1","key2":"value2"}']);
        $this->assertSame(['key', 'elem', '{"key1":"value1","key2":"value2"}'], $command->getArguments());
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
    public function testSetAttributesToGivenElement(): void
    {
        $redis = $this->getClient();

        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10)
        );

        $this->assertTrue($redis->vsetattr('key', 'elem1', '{"key1":"value1"}'));
        $this->assertEquals(['key1' => 'value1'], $redis->vgetattr('key', 'elem1'));
        $this->assertTrue($redis->vsetattr('key', 'elem1', ['key2' => 'value2']));
        $this->assertEquals(['key2' => 'value2'], $redis->vgetattr('key', 'elem1'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testSetAttributesToGivenElementResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10)
        );

        $this->assertTrue($redis->vsetattr('key', 'elem1', '{"key1":"value1"}'));
        $this->assertEquals(['key1' => 'value1'], $redis->vgetattr('key', 'elem1'));
        $this->assertTrue($redis->vsetattr('key', 'elem1', ['key2' => 'value2']));
        $this->assertEquals(['key2' => 'value2'], $redis->vgetattr('key', 'elem1'));
    }
}
