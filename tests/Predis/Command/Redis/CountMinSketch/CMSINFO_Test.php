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
class CMSINFO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CMSINFO::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CMSINFO';
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
        $actualResponse = ['width', 2000, 'depth', 10, 'count', 0];
        $expectedResponse = ['width' => 2000, 'depth' => 10, 'count' => 0];

        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testReturnsInfoAboutGivenCountMinSketch(): void
    {
        $redis = $this->getClient();
        $expectedResponse = ['width' => 2000, 'depth' => 10, 'count' => 0];

        $redis->cmsinitbydim('key', 2000, 10);

        $actualResponse = $redis->cmsinfo('key');

        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: key does not exist');

        $redis = $this->getClient();

        $redis->cmsinfo('cmsinfo_foo');
    }
}
