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

/**
 * @group commands
 * @group realm-stream
 */
class XCFGSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XCFGSET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XCFGSET';
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
        $arguments = ['stream', 100, 1000];
        $expected = ['prefix:stream', 'IDMP-DURATION', 100, 'IDMP-MAXSIZE', 1000];

        $command = $this->getCommandWithArgumentsArray($arguments);
        $command->prefixKeys('prefix:');

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.5.0
     */
    public function testConfigureStreamIdempotencyParameters(): void
    {
        $redis = $this->getClient();

        // Create a stream first
        $redis->xadd('stream', ['field' => 'value']);

        // Configure IDMP parameters
        $response = $redis->xcfgset('stream', 100, 1000);

        $this->assertEquals('OK', $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.5.0
     */
    public function testIIDsAreFlushedAfterDuration(): void
    {
        $redis = $this->getClient();

        // Configure stream with very short IDMP-DURATION (2 seconds)
        $redis->xadd('stream', ['field' => 'value']);
        $redis->xcfgset('stream', 1, 1000);

        // Add message with IID using IDMP
        $id1 = $redis->xadd('stream', ['field' => 'value1'], '*', ['idmp' => ['producer1', 'iid-1']]);

        // Try to add duplicate immediately - should return same ID (idempotent)
        $id2 = $redis->xadd('stream', ['field' => 'value2'], '*', ['idmp' => ['producer1', 'iid-1']]);
        $this->assertSame($id1, $id2);

        // Wait for duration to expire (2 seconds + buffer)
        sleep(2);

        // Now the same IID should create a new entry (IID was flushed)
        $id3 = $redis->xadd('stream', ['field' => 'value3'], '*', ['idmp' => ['producer1', 'iid-1']]);
        $this->assertNotSame($id1, $id3);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.5.0
     */
    public function testIIDsAreFlushedWhenExceedingMaxsize(): void
    {
        $redis = $this->getClient();

        // Configure stream with small IDMP-MAXSIZE (3 IIDs)
        $redis->xadd('stream', ['field' => 'value']);
        $redis->xcfgset('stream', 100, 3);

        // Add 3 messages with different IIDs (fills the IDMP map)
        $id1 = $redis->xadd('stream', ['field' => 'value1'], '*', ['idmp' => ['producer1', 'iid-1']]);
        $id2 = $redis->xadd('stream', ['field' => 'value2'], '*', ['idmp' => ['producer1', 'iid-2']]);
        $id3 = $redis->xadd('stream', ['field' => 'value3'], '*', ['idmp' => ['producer1', 'iid-3']]);

        // Try to add duplicate of iid-1 - should return same ID (idempotent)
        $id1Dup = $redis->xadd('stream', ['field' => 'duplicate'], '*', ['idmp' => ['producer1', 'iid-1']]);
        $this->assertSame($id1, $id1Dup);

        // Add a 4th unique IID - this should cause oldest IID (iid-1) to be flushed
        $id4 = $redis->xadd('stream', ['field' => 'value4'], '*', ['idmp' => ['producer1', 'iid-4']]);
        $this->assertNotNull($id4);

        // Now iid-1 should create a new entry (it was flushed due to maxsize)
        $id1New = $redis->xadd('stream', ['field' => 'value5'], '*', ['idmp' => ['producer1', 'iid-1']]);
        $this->assertNotSame($id1, $id1New);
    }

    public function argumentsProvider(): array
    {
        return [
            'with key only' => [
                ['stream'],
                ['stream'],
            ],
            'with IDMP-DURATION only' => [
                ['stream', 100],
                ['stream', 'IDMP-DURATION', 100],
            ],
            'with IDMP-MAXSIZE only' => [
                ['stream', null, 1000],
                ['stream', 'IDMP-MAXSIZE', 1000],
            ],
            'with both IDMP-DURATION and IDMP-MAXSIZE' => [
                ['stream', 100, 1000],
                ['stream', 'IDMP-DURATION', 100, 'IDMP-MAXSIZE', 1000],
            ],
        ];
    }
}
