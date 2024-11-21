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

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CMSINITBYDIM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CMSINITBYDIM::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CMSINITBYDIM';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 2000, 10];
        $expectedArguments = ['key', 2000, 10];

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
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testInitializeCountMinSketchWithGivenConfiguration(): void
    {
        $redis = $this->getClient();

        $actualResponse = $redis->cmsinitbydim('key', 2000, 10);
        $info = $redis->cmsinfo('key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(2000, $info['width']);
        $this->assertSame(10, $info['depth']);
    }

    /**
     * @group connected
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnAlreadyExistingKey(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: key already exists');

        $redis = $this->getClient();

        $redis->set('cmsinitbydim_foo', 'bar');
        $redis->cmsinitbydim('cmsinitbydim_foo', 2000, 10);
    }
}
