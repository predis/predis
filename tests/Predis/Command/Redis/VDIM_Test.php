<?php

namespace Predis\Command\Redis;

use Predis\Command\Redis\Utils\VectorUtility;

class VDIM_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return VDIM::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'VDIM';
    }

    /**
     * @return void
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key']);

        $this->assertSame(['key'], $command->getArguments());
    }

    /**
     * @return void
     */
    public function testParseResponse(): void
    {
        $this->assertEquals(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsVectorSetDimensions(): void
    {
        $redis = $this->getClient();

        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10)
        );
        $this->assertEquals(10, $redis->vdim('key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsVectorSetDimensionsResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10)
        );
        $this->assertEquals(10, $redis->vdim('key'));
    }
}
