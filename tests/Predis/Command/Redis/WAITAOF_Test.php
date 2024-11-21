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

namespace Predis\Command\Redis;

class WAITAOF_Test extends PredisCommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $redis = $this->getClient();
        $this->assertEquals('OK', $redis->config('set', 'appendonly', 'yes'));
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return WAITAOF::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'WAITAOF';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = $expectedArguments = [1, 2, 3];

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
     * @return void
     * @requiresRedisVersion >= 7.2.0
     */
    public function testReturnQuantityOfSyncedAOFInstances(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertSame([1, 0], $redis->waitaof(1, 0, 0));
    }

    protected function tearDown(): void
    {
        $redis = $this->getClient();
        $this->assertEquals('OK', $redis->config('set', 'appendonly', 'no'));
    }
}
