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

class VRANDMEMBER_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return VRANDMEMBER::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'VRANDMEMBER';
    }

    /**
     * @return void
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key']);

        $this->assertSame(['key'], $command->getArguments());

        $command->setArguments(['key', 3]);
        $this->assertSame(['key', 3], $command->getArguments());

        $command->setArguments(['key', null]);
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
    public function testReturnsRandomMember(): void
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

        // With no count
        $this->assertTrue(in_array($redis->vrandmember('key'), ['elem1', 'elem2', 'elem3']));

        foreach ($redis->vrandmember('key', 2) as $elem) {
            $this->assertTrue(in_array($elem, ['elem1', 'elem2', 'elem3']));
        }

        $this->assertNull($redis->vrandmember('wrong'));
        $this->assertEmpty($redis->vrandmember('wrong', 2));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsRandomMemberResp3(): void
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

        // With no count
        $this->assertTrue(in_array($redis->vrandmember('key'), ['elem1', 'elem2', 'elem3']));

        foreach ($redis->vrandmember('key', 2) as $elem) {
            $this->assertTrue(in_array($elem, ['elem1', 'elem2', 'elem3']));
        }

        $this->assertNull($redis->vrandmember('wrong'));
        $this->assertEmpty($redis->vrandmember('wrong', 2));
    }
}
