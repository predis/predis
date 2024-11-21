<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\TopK;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TOPKINFO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TOPKINFO::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TOPKINFO';
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
        $actualResponse = ['k', 50, 'width', 8, 'depth', 7, 'decay', '0.90000000000000002'];
        $expectedResponse = ['k' => 50, 'width' => 8, 'depth' => 7, 'decay' => '0.90000000000000002'];

        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testReturnsInfoAboutGivenTopKStructure(): void
    {
        $redis = $this->getClient();

        $redis->topkreserve('key', 50);

        $this->assertSameWithPrecision(
            ['k' => 50, 'width' => 8, 'depth' => 7, 'decay' => '0.90000000000000002'],
            $redis->topkinfo('key'),
            1
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('TopK: key does not exist');

        $redis->topkinfo('key');
    }
}
