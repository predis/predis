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

/**
 * @group commands
 * @group realm-stream
 */
class XDELEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XDELEX';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XDELEX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithKEEPREF(): void
    {
        $arguments = ['stream', 'KEEPREF', ['id1', 'id2']];
        $expected = ['stream', 'KEEPREF', 'IDS', '2', 'id1', 'id2'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithDELREF(): void
    {
        $arguments = ['stream', 'DELREF', ['id1', 'id2', 'id3']];
        $expected = ['stream', 'DELREF', 'IDS', '3', 'id1', 'id2', 'id3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithACKED(): void
    {
        $arguments = ['stream', 'ACKED', ['id1']];
        $expected = ['stream', 'ACKED', 'IDS', '1', 'id1'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame([1, 2, -1], $this->getCommand()->parseResponse([1, 2, -1]));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['stream', 'DELREF', ['id1', 'id2']];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:stream', 'DELREF', 'IDS', '2', 'id1', 'id2'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.2.0
     */
    public function testRemovesSpecifiedMembersWithXdelex(): void
    {
        $redis = $this->getClient();

        $redis->xadd('teststream', ['key0' => 'val0'], '0-1');
        $redis->xadd('teststream', ['key1' => 'val1'], '1-1');
        $redis->xadd('teststream', ['key2' => 'val2'], '2-1');

        $result = $redis->xdelex('teststream', 'KEEPREF', ['0-1', '2-1']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $remaining = $redis->xrange('teststream', '-', '+');
        $this->assertSame(['1-1' => ['key1' => 'val1']], $remaining);

        $redis->xadd('teststream', ['key3' => 'val3'], '3-1');
        $result2 = $redis->xdelex('teststream', 'KEEPREF', ['3-1']);

        $this->assertIsArray($result2);
        $this->assertCount(1, $result2);
    }
}
