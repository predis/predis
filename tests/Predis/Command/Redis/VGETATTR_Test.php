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

class VGETATTR_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return VGETATTR::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'VGETATTR';
    }

    /**
     * @return void
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key', 'value']);

        $this->assertSame(['key', 'value'], $command->getArguments());

        $command->setArguments(['key', 'value', false]);
        $this->assertSame(['key', 'value'], $command->getArguments());
    }

    /**
     * @return void
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key', 'value']);

        $this->assertEquals(['key' => 'value'], $command->parseResponse('{"key":"value"}'));

        $command->setArguments(['key', 'value', true]);
        $this->assertEquals('{"key":"value"}', $command->parseResponse('{"key":"value"}'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsVectorSetElementAttributes(): void
    {
        $redis = $this->getClient();

        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10, false,
                null, null, ['key' => 'value', 'key1' => 'value1']
            )
        );

        $this->assertEquals(['key' => 'value', 'key1' => 'value1'], $redis->vgetattr('key', 'elem1'));
        $this->assertEquals(
            '{"key":"value","key1":"value1"}',
            $redis->vgetattr('key', 'elem1', true)
        );
        $this->assertNull($redis->vgetattr('wrong', 'elem1'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsVectorSetElementAttributesResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10, false,
                null, null, ['key' => 'value', 'key1' => 'value1']
            )
        );

        $this->assertEquals(['key' => 'value', 'key1' => 'value1'], $redis->vgetattr('key', 'elem1'));
        $this->assertEquals(
            '{"key":"value","key1":"value1"}',
            $redis->vgetattr('key', 'elem1', true)
        );
        $this->assertNull($redis->vgetattr('wrong', 'elem1'));
    }
}
