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

use Predis\Response\ServerException;

class XREAD_NEW_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return XREAD_NEW::class;
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

        $response = $redis->xread_new(['stream1' => '0-0', 'stream2' => '0-0'], 2);

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testPrefixStreamKeys(): void
    {
        $redis = $this->createClient(null, ['prefix' => 'prefix:']);

        $stream1Id = $redis->xadd('stream1', ['field' => 'value']);
        $stream2Id = $redis->xadd('stream2', ['field' => 'value']);

        $expectedResponse = [
            'prefix:stream1' => [
                [$stream1Id, ['field', 'value']],
            ],
            'prefix:stream2' => [
                [$stream2Id, ['field', 'value']],
            ],
        ];

        $response = $redis->xread_new(['stream1' => '0-0', 'stream2' => '0-0']);

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

        $stream1Id = $redis->xadd('stream1', ['field' => 'value']);
        $redis->xdel('stream1', $stream1Id);
        $response = $redis->xread_new(['stream1' => '0-0'], 1);

        $this->assertSame([], $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadWithMaxCountCapsCumulativeEntriesAcrossStreams(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream1', ['field' => 'value1'], '1-0');
        $redis->xadd('stream1', ['field' => 'value2'], '2-0');
        $redis->xadd('stream2', ['field' => 'value3'], '1-0');
        $redis->xadd('stream2', ['field' => 'value4'], '2-0');

        // COUNT is per-stream: without MAXCOUNT all 4 entries are returned.
        $response = $redis->xread_new(['stream1' => '0-0', 'stream2' => '0-0'], 2);
        $this->assertSame(4, count($response['stream1']) + count($response['stream2']));

        // MAXCOUNT caps the cumulative reply, streams are served in caller order.
        $expectedResponse = [
            'stream1' => [
                ['1-0', ['field', 'value1']],
                ['2-0', ['field', 'value2']],
            ],
            'stream2' => [
                ['1-0', ['field', 'value3']],
            ],
        ];

        $response = $redis->xread_new(['stream1' => '0-0', 'stream2' => '0-0'], 2, null, 3);
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadWithMaxCountWithoutCount(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream1', ['field' => 'value1'], '1-0');
        $redis->xadd('stream1', ['field' => 'value2'], '2-0');
        $redis->xadd('stream2', ['field' => 'value3'], '1-0');

        // Streams are served in caller order, so both entries come from stream1.
        $expectedResponse = [
            'stream1' => [
                ['1-0', ['field', 'value1']],
                ['2-0', ['field', 'value2']],
            ],
        ];

        $response = $redis->xread_new(['stream1' => '0-0', 'stream2' => '0-0'], null, null, 2);
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadWithMaxSizeAllowsFirstOversizedEntry(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream1', ['field' => 'value1'], '1-0');
        $redis->xadd('stream1', ['field' => 'value2'], '2-0');
        $redis->xadd('stream2', ['field' => 'value3'], '1-0');

        // MAXSIZE is a soft cap: the first available entry is returned even
        // when it alone exceeds the byte budget, remaining streams are skipped.
        $expectedResponse = [
            'stream1' => [
                ['1-0', ['field', 'value1']],
            ],
        ];

        $response = $redis->xread_new(['stream1' => '0-0', 'stream2' => '0-0'], null, null, null, 1);
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadWithMaxSizeLargeEnoughReturnsAllEntries(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream1', ['field' => 'value1'], '1-0');
        $redis->xadd('stream2', ['field' => 'value2'], '1-0');

        $expectedResponse = [
            'stream1' => [
                ['1-0', ['field', 'value1']],
            ],
            'stream2' => [
                ['1-0', ['field', 'value2']],
            ],
        ];

        $response = $redis->xread_new(['stream1' => '0-0', 'stream2' => '0-0'], null, null, null, 1048576);
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadWithMaxCountAndMaxSizeResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->xadd('stream1', ['field' => 'value1'], '1-0');
        $redis->xadd('stream1', ['field' => 'value2'], '2-0');
        $redis->xadd('stream2', ['field' => 'value3'], '1-0');

        $expectedResponse = [
            'stream1' => [
                ['1-0', ['field', 'value1']],
                ['2-0', ['field', 'value2']],
            ],
        ];

        $response = $redis->xread_new(['stream1' => '0-0', 'stream2' => '0-0'], null, null, 2, 1048576);
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadWithMaxCountPreservesNoMessagesReply(): void
    {
        $redis = $this->getClient();

        $streamId = $redis->xadd('stream1', ['field' => 'value1']);
        $redis->xdel('stream1', $streamId);

        $response = $redis->xread_new(['stream1' => '0-0'], null, null, 2, 1024);

        $this->assertSame([], $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testThrowsExceptionOnMaxCountLowerThanCount(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream1', ['field' => 'value1']);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('MAXCOUNT must be greater than or equal to COUNT');

        $redis->xread_new(['stream1' => '0-0'], 2, null, 1);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testThrowsExceptionOnNonPositiveMaxCount(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream1', ['field' => 'value1']);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('MAXCOUNT must be a positive integer');

        $redis->xread_new(['stream1' => '0-0'], null, null, 0);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testThrowsExceptionOnNonPositiveMaxSize(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream1', ['field' => 'value1']);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('MAXSIZE must be a positive integer');

        $redis->xread_new(['stream1' => '0-0'], null, null, null, 0);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                [['stream' => '0-0']],
                ['STREAMS', 'stream', '0-0'],
            ],
            'with multiple streams' => [
                [['stream' => '0-0', 'stream1' => '0-1']],
                ['STREAMS', 'stream', 'stream1', '0-0', '0-1'],
            ],
            'with COUNT modifier' => [
                [['stream' => '0-0'], 10],
                ['COUNT', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with BLOCK modifier' => [
                [['stream' => '0-0'], null, 10],
                ['BLOCK', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with MAXCOUNT modifier' => [
                [['stream' => '0-0'], null, null, 10],
                ['MAXCOUNT', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with MAXSIZE modifier' => [
                [['stream' => '0-0'], null, null, null, 65536],
                ['MAXSIZE', 65536, 'STREAMS', 'stream', '0-0'],
            ],
            'with COUNT, MAXCOUNT and MAXSIZE modifiers' => [
                [['stream' => '0-0'], 10, null, 20, 65536],
                ['COUNT', 10, 'MAXCOUNT', 20, 'MAXSIZE', 65536, 'STREAMS', 'stream', '0-0'],
            ],
            'with all arguments' => [
                [['stream' => '0-0', 'stream1' => '0-1'], 10, 10, 20, 65536],
                ['COUNT', 10, 'MAXCOUNT', 20, 'MAXSIZE', 65536, 'BLOCK', 10, 'STREAMS', 'stream', 'stream1', '0-0', '0-1'],
            ],
        ];
    }
}
