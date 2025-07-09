<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

class GETDEL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return GETDEL::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'GETDEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key'];
        $expected = ['key'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
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
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsValueForGivenKeyAndDeleteIt(): void
    {
        $redis = $this->getClient();
        $expectedKey = 'key';
        $expectedValue = 'value';

        $redis->set($expectedKey, $expectedValue);

        $actualResponse = $redis->getdel($expectedKey);

        $this->assertSame($expectedValue, $actualResponse);
        $this->assertNull($redis->get($expectedKey));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsValueForGivenKeyAndDeleteItResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedKey = 'key';
        $expectedValue = 'value';

        $redis->set($expectedKey, $expectedValue);

        $actualResponse = $redis->getdel($expectedKey);

        $this->assertSame($expectedValue, $actualResponse);
        $this->assertNull($redis->get($expectedKey));
    }
}
