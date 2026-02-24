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

use Predis\ClientInterface;

/**
 * @group commands
 * @group realm-stream
 */
class XPENDING_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XPENDING';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XPENDING';
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
        $command = $this->getCommand();

        // Regular format
        $raw = [4, '0-1', '3-1', [['consumer1', '1'], ['consumer2', '2'], ['consumer3', '1']]];
        $expected = [4, '0-1', '3-1', ['consumer1' => 1, 'consumer2' => 2, 'consumer3' => 1]];
        $this->assertSame($expected, $command->parseResponse($raw));
        $this->assertSame($expected, $command->parseResp3Response($raw));

        // Extended format
        $command->setRawArguments(['stream', 'group', '-', '+', 10]);
        $raw = [['0-1', 'consumer1', 0, 1], ['1-1', 'consumer2', 0, 1]];
        $expected = [['0-1', 'consumer1', 0, 1], ['1-1', 'consumer2', 0, 1]];
        $this->assertSame($expected, $command->parseResponse($raw));
        $this->assertSame($expected, $command->parseResp3Response($raw));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        $arguments = ['stream', 'group'];
        $expected = ['prefix:stream', 'group'];

        $command = $this->getCommandWithArgumentsArray($arguments);
        $command->prefixKeys('prefix:');

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testPending(): void
    {
        $redis = $this->getClient();
        $this->testPendingWithClient($redis);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testPendingResp3(): void
    {
        $redis = $this->getResp3Client();
        $this->testPendingWithClient($redis);
    }

    private function testPendingWithClient(ClientInterface $redis): void
    {
        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xadd('stream', ['key1' => 'val1'], '1-1');
        $redis->xadd('stream', ['key2' => 'val2'], '2-1');
        $redis->xadd('stream', ['key3' => 'val3'], '3-1');

        $redis->xgroup->create('stream', 'group', '0');

        $redis->xreadgroup('group', 'consumer1', 1, null, false, 'stream', '>');
        $redis->xreadgroup('group', 'consumer2', 2, null, false, 'stream', '>');
        $redis->xreadgroup('group', 'consumer3', 1, null, false, 'stream', '>');

        $pending = $redis->xpending('stream', 'group');
        $this->assertSame([4, '0-1', '3-1', ['consumer1' => 1, 'consumer2' => 2, 'consumer3' => 1]], $pending);

        $pending = $redis->xpending('stream', 'group', null, '-', '+', 2);
        $this->assertSameExceptTime([['0-1', 'consumer1', 0, 1], ['1-1', 'consumer2', 0, 1]], $pending);
        $pending = $redis->xpending('stream', 'group', null, '-', '+', 2, 'consumer3');
        $this->assertSameExceptTime([['3-1', 'consumer3', 0, 1]], $pending);
    }

    private function assertSameExceptTime(array $expected, array $actual): void
    {
        array_map(function ($expected, $actual) {
            unset($expected[2], $actual[2]);
            $this->assertSame($expected, $actual);
        }, $expected, $actual);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['stream', 'group'],
                ['stream', 'group'],
            ],
            'with start end count' => [
                ['stream', 'group', null, '-', '+', 10],
                ['stream', 'group', '-', '+', 10],
            ],
            'with consumer' => [
                ['stream', 'group', null, '-', '+', 10, 'consumer'],
                ['stream', 'group', '-', '+', 10, 'consumer'],
            ],
            'with IDLE modifier' => [
                ['stream', 'group', 100, '-', '+', 10],
                ['stream', 'group', 'IDLE', 100, '-', '+', 10],
            ],
            'with all arguments' => [
                ['stream', 'group', 100, '-', '+', 10, 'consumer'],
                ['stream', 'group', 'IDLE', 100, '-', '+', 10, 'consumer'],
            ],
        ];
    }
}
