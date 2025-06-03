<?php

namespace Predis\Command\Redis;

use Predis\Command\Redis\Utils\VectorUtility;

class VCARD_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return VCARD::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'VCARD';
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
    public function testReturnsVectorSetCardinality(): void
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
        $this->assertEquals(3, $redis->vcard('key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsVectorSetCardinalityResp3(): void
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
        $this->assertEquals(3, $redis->vcard('key'));
    }
}
