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

class XREAD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return XREAD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'XREAD';
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
        $this->assertSame(
            ['stream1' => [['id1', ['field', 'value']], ['id2', ['field', 'value']]]],
            $this->getCommand()->parseResponse(
                [['stream1', [['id1', ['field', 'value']], ['id2', ['field', 'value']]]]])
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReadFromTheBeginningOfGivenStreams(): void
    {
        $redis = $this->getClient();

        $stream1Id = $redis->xadd('stream1', ['field' => 'value']);
        $stream2Id = $redis->xadd('stream2', ['field' => 'value']);

        $expectedResponse = [
            'stream1' => [
                [$stream1Id, ['field', 'value']],
            ],
            'stream2' => [
                [$stream2Id, ['field', 'value']],
            ],
        ];

        $response = $redis->xread(2, null, ['stream1', 'stream2'], '0-0', '0-0');

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReadFromTheBeginningOfGivenStreamsResp3(): void
    {
        $redis = $this->getResp3Client();

        $stream1Id = $redis->xadd('stream1', ['field' => 'value']);
        $stream2Id = $redis->xadd('stream2', ['field' => 'value']);

        $expectedResponse = [
            'stream1' => [
                [$stream1Id, ['field', 'value']],
            ],
            'stream2' => [
                [$stream2Id, ['field', 'value']],
            ],
        ];

        $response = $redis->xread(2, null, ['stream1', 'stream2'], '0-0', '0-0');

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group medium
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReadFromTheBeginningOfGivenStreamsWithBlocking(): void
    {
        $redis = $this->getClient();

        $stream1Id = $redis->xadd('stream1', ['field' => 'value']);
        $stream2Id = $redis->xadd('stream2', ['field' => 'value']);

        $expectedResponse = [
            'stream1' => [
                [$stream1Id, ['field', 'value']],
            ],
            'stream2' => [
                [$stream2Id, ['field', 'value']],
            ],
        ];

        $response = $redis->xread(2, 20, ['stream1', 'stream2'], '0-0', '0-0');

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.4.0
     */
    public function testReadFromGivenStreamsStartingFromLastAvailableId(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream1', ['otherField' => 'otherValue']);
        $redis->xadd('stream2', ['otherField' => 'otherValue']);

        $stream1Id = $redis->xadd('stream1', ['field' => 'value']);
        $stream2Id = $redis->xadd('stream2', ['field' => 'value']);

        $expectedResponse = [
            'stream1' => [
                [$stream1Id, ['field', 'value']],
            ],
            'stream2' => [
                [$stream2Id, ['field', 'value']],
            ],
        ];

        $response = $redis->xread(2, null, ['stream1', 'stream2'], '+', '+');

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     * @return void
     */
    public function testReadNull(): void
    {
        $redis = $this->getClient();

        $stream1Id1 = $redis->xadd('stream1', ['field1' => 'value1']);
        $redis->xdel('stream1', $stream1Id1);
        $response = $redis->xread(1, null, ['stream1'], '0-0');

        $this->assertSame([], $response);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                [null, null, null, '0-0', '0-1'],
                ['0-0', '0-1'],
            ],
            'with COUNT argument' => [
                [2, null, null, '0-0', '0-1'],
                ['COUNT', 2, '0-0', '0-1'],
            ],
            'with BLOCK argument' => [
                [null, 20, null, '0-0', '0-1'],
                ['BLOCK', 20, '0-0', '0-1'],
            ],
            'with STREAMS argument' => [
                [null, null, ['key1', 'key2'], '0-0', '0-1'],
                ['STREAMS', 'key1', 'key2', '0-0', '0-1'],
            ],
            'with all arguments' => [
                [2, 20, ['key1', 'key2'], '0-0', '0-1'],
                ['COUNT', 2, 'BLOCK', 20, 'STREAMS', 'key1', 'key2', '0-0', '0-1'],
            ],
        ];
    }
}
