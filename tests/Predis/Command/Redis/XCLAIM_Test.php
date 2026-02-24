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
class XCLAIM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XCLAIM';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XCLAIM';
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

        $raw = [['1-1', ['key1', 'val1']], ['2-1', ['key2', 'val2']]];
        $expected = ['1-1' => ['key1' => 'val1'], '2-1' => ['key2' => 'val2']];
        $this->assertSame($expected, $command->parseResponse($raw));
        $this->assertSame($expected, $command->parseResp3Response($raw));

        // JUSTID format
        $raw = ['1-1', '2-1'];
        $expected = ['1-1', '2-1'];
        $this->assertSame($expected, $command->parseResponse($raw));
        $this->assertSame($expected, $command->parseResp3Response($raw));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        $arguments = ['stream', 'group', 'consumer', 0, 'id1'];
        $expected = ['prefix:stream', 'group', 'consumer', 0, 'id1'];

        $command = $this->getCommandWithArgumentsArray($arguments);
        $command->prefixKeys('prefix:');

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testClaim(): void
    {
        $redis = $this->getClient();
        $this->testClaimWithClient($redis);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testClaimResp3(): void
    {
        $redis = $this->getResp3Client();
        $this->testClaimWithClient($redis);
    }

    private function testClaimWithClient(ClientInterface $redis): void
    {
        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xadd('stream', ['key1' => 'val1'], '1-1');
        $redis->xadd('stream', ['key2' => 'val2'], '2-1');
        $redis->xadd('stream', ['key3' => 'val3'], '3-1');

        $redis->xgroup->create('stream', 'group', '0');

        $redis->xreadgroup('group', 'consumer1', 4, null, false, 'stream', '>');

        // Claim one
        $claimed = $redis->xclaim('stream', 'group', 'consumer2', 0, '0-1');
        $this->assertSame(['0-1' => ['key0' => 'val0']], $claimed);

        // Claim many
        $claimed = $redis->xclaim('stream', 'group', 'consumer2', 0, ['1-1', '2-1']);
        $this->assertSame(['1-1' => ['key1' => 'val1'], '2-1' => ['key2' => 'val2']], $claimed);

        // Claim deleted
        $redis->xdel('stream', '1-1');
        $claimed = $redis->xclaim('stream', 'group', 'consumer3', 0, ['0-1', '1-1', '2-1'], null, null, null, false, true);
        $this->assertSame(['0-1', '2-1'], $claimed);

        // Claim with all options
        $redis->xdel('stream', '1-1');
        $claimed = $redis->xclaim('stream', 'group', 'consumer3', 0, '3-1', 10, 100, 5, true, true, '3-1');
        $this->assertSame(['3-1'], $claimed);

        // Claim unknown
        $claimed = $redis->xclaim('stream', 'group', 'consumer3', 0, ['4-1']);
        $this->assertSame([], $claimed);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['stream', 'group', 'consumer', 0, 'id1'],
                ['stream', 'group', 'consumer', 0, 'id1'],
            ],
            'with array ids' => [
                ['stream', 'group', 'consumer', 0, ['id1', 'id2']],
                ['stream', 'group', 'consumer', 0, 'id1', 'id2'],
            ],
            'with IDLE modifier' => [
                ['stream', 'group', 'consumer', 0, ['id1', 'id2'], 10],
                ['stream', 'group', 'consumer', 0, 'id1', 'id2', 'IDLE', 10],
            ],
            'with TIME modifier' => [
                ['stream', 'group', 'consumer', 0, ['id1', 'id2'], null, 12345],
                ['stream', 'group', 'consumer', 0, 'id1', 'id2', 'TIME', 12345],
            ],
            'with RETRYCOUNT modifier' => [
                ['stream', 'group', 'consumer', 0, ['id1', 'id2'], null, null, 5],
                ['stream', 'group', 'consumer', 0, 'id1', 'id2', 'RETRYCOUNT', 5],
            ],
            'with FORCE modifier' => [
                ['stream', 'group', 'consumer', 0, ['id1', 'id2'], null, null, null, true],
                ['stream', 'group', 'consumer', 0, 'id1', 'id2', 'FORCE'],
            ],
            'with JUSTID modifier' => [
                ['stream', 'group', 'consumer', 0, ['id1', 'id2'], null, null, null, false, true],
                ['stream', 'group', 'consumer', 0, 'id1', 'id2', 'JUSTID'],
            ],
            'with LASTID modifier' => [
                ['stream', 'group', 'consumer', 0, ['id1', 'id2'], null, null, null, false, false, '1-1'],
                ['stream', 'group', 'consumer', 0, 'id1', 'id2', 'LASTID', '1-1'],
            ],
            'with all arguments' => [
                ['stream', 'group', 'consumer', 100, ['id1', 'id2'], 10, 12345, 5, true, true, '1-1'],
                ['stream', 'group', 'consumer', 100, 'id1', 'id2', 'IDLE', 10, 'TIME', 12345, 'RETRYCOUNT', 5, 'FORCE', 'JUSTID', 'LASTID', '1-1'],
            ],
        ];
    }
}
