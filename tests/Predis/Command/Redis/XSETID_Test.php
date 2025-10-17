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

use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stream
 */
class XSETID_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XSETID';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XSETID';
    }

    /**
     * @dataProvider argumentsProvider
     * @group disconnected
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        $arguments = ['stream', '100-1'];
        $expected = ['prefix:stream', '100-1'];

        $command = $this->getCommandWithArgumentsArray($arguments);
        $command->prefixKeys('prefix:');

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testSetId(): void
    {
        $redis = $this->getClient();
        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $this->assertEquals('OK', $redis->xsetid('stream', '1-1', 500, '1-1'));

        // Attempt to set id less than top id
        $redis->xadd('stream', ['key0' => 'val0'], '2-1');
        $this->expectException(ServerException::class);
        $redis->xsetid('stream', '1-1');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testSetIdExtended(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');

        // Basic
        $this->assertEquals('OK', $redis->xsetid('stream', '1-1'));
        $xinfo = $redis->xinfo->stream('stream');
        $this->assertSame('1-1', $xinfo['last-generated-id']);

        // Extended
        $this->assertEquals('OK', $redis->xsetid('stream', '2-1', 500, '1-1'));
        $xinfo = $redis->xinfo->stream('stream');
        $this->assertSame('2-1', $xinfo['last-generated-id']);
        $this->assertSame(500, $xinfo['entries-added']);
        $this->assertSame('1-1', $xinfo['max-deleted-entry-id']);

        // Rewind
        $this->assertEquals('OK', $redis->xsetid('stream', '1-1', 500, '1-1'));
        $xinfo = $redis->xinfo->stream('stream');
        $this->assertSame('1-1', $xinfo['last-generated-id']);

        // Attempt to set id less than top id
        $redis->xadd('stream', ['key0' => 'val0'], '2-1');
        $this->expectException(ServerException::class);
        $redis->xsetid('stream', '1-1');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['stream', '100-1'],
                ['stream', '100-1'],
            ],
            'with ENTRIESADDED modifier' => [
                ['stream', '100-1', 500],
                ['stream', '100-1', 'ENTRIESADDED', 500],
            ],
            'with MAXDELETEDID modifier' => [
                ['stream', '100-1', null, '50-1'],
                ['stream', '100-1', 'MAXDELETEDID', '50-1'],
            ],
            'with all arguments' => [
                ['stream', '100-1', 500, '50-1'],
                ['stream', '100-1', 'ENTRIESADDED', 500, 'MAXDELETEDID', '50-1'],
            ],
        ];
    }
}
