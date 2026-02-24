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

use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\Utils\VectorUtility;

class VRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return VRANGE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'VRANGE';
    }

    /**
     * @return void
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['key', 'start', 'end']);

        $this->assertSame(['key', 'start', 'end'], $command->getArguments());

        $command->setArguments(['key', 'start', 'end', 3]);
        $this->assertSame(['key', 'start', 'end', 3], $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['key', '-', '+'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:key', '-', '+'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
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
     * @requiresRedisVersion >= 8.4.0
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
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem3', 10)
        );

        $this->assertSame(['elem1', 'elem2', 'elem3'], $redis->vrange('key', '-', '+'));
        $this->assertSame(['elem1', 'elem2'], $redis->vrange('key', '-', '+', 2));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.4.0
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
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem3', 10)
        );

        $this->assertSame(['elem1', 'elem2', 'elem3'], $redis->vrange('key', '-', '+'));
        $this->assertSame(['elem1', 'elem2'], $redis->vrange('key', '-', '+', 2));
    }
}
