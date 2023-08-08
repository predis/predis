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

namespace Predis\Command\Redis\TopK;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TOPKQUERY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TOPKQUERY::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TOPKQUERY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 'item1', 'item2'];
        $expectedArguments = ['key', 'item1', 'item2'];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testQueryChecksIfItemExistsWithinGivenTopKStructure(): void
    {
        $redis = $this->getClient();

        $redis->topkreserve('key', 50);
        $redis->topkadd('key', 'item1');

        $this->assertSame([1, 0], $redis->topkquery('key', 'item1', 'item2'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testQueryChecksIfItemExistsWithinGivenTopKStructureResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->topkreserve('key', 50);
        $redis->topkadd('key', 'item1');

        $this->assertSame([true, false], $redis->topkquery('key', 'item1', 'item2'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('TopK: key does not exist');

        $redis->topkquery('key', 0, 1);
    }
}
