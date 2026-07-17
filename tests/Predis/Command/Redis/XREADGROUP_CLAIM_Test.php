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

class XREADGROUP_CLAIM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return XREADGROUP_CLAIM::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'XREADGROUP';
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
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReadsFromGivenConsumerGroup(): void
    {
        $redis = $this->getClient();

        $streamInitId = $redis->xadd('stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', $streamInitId));

        $nextId = $redis->xadd('stream', ['newField' => 'newValue']);
        $expectedResponse = [
            [
                'stream',
                [
                    [$nextId, ['newField', 'newValue']],
                ],
            ],
        ];

        $this->assertSame(
            $expectedResponse,
            $redis->xreadgroup_claim(
                'group',
                'consumer',
                ['stream' => '>']
            )
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReadsFromConsumerGroupFromMultipleStreams(): void
    {
        $redis = $this->getClient();

        $streamInitId = $redis->xadd('stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', $streamInitId));

        $anotherStreamInitId = $redis->xadd('another_stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('another_stream', 'group', $anotherStreamInitId));

        $nextId = $redis->xadd('stream', ['newField' => 'newValue']);
        $anotherNextId = $redis->xadd('another_stream', ['newField' => 'newValue']);

        $expectedResponse = [
            [
                'stream',
                [
                    [$nextId, ['newField', 'newValue']],
                ],
            ],
            [
                'another_stream',
                [
                    [$anotherNextId, ['newField', 'newValue']],
                ],
            ],
        ];

        $this->assertSame(
            $expectedResponse,
            $redis->xreadgroup_claim(
                'group',
                'consumer',
                ['stream' => '>', 'another_stream' => '>']
            )
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testPrefixStreamKeys(): void
    {
        $redis = $this->createClient(null, ['prefix' => 'prefix:']);

        $streamInitId = $redis->xadd('stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', $streamInitId));

        $anotherStreamInitId = $redis->xadd('another_stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('another_stream', 'group', $anotherStreamInitId));

        $nextId = $redis->xadd('stream', ['newField' => 'newValue']);
        $anotherNextId = $redis->xadd('another_stream', ['newField' => 'newValue']);

        $expectedResponse = [
            [
                'prefix:stream',
                [
                    [$nextId, ['newField', 'newValue']],
                ],
            ],
            [
                'prefix:another_stream',
                [
                    [$anotherNextId, ['newField', 'newValue']],
                ],
            ],
        ];

        $this->assertSame(
            $expectedResponse,
            $redis->xreadgroup_claim(
                'group',
                'consumer',
                ['stream' => '>', 'another_stream' => '>']
            )
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.3.224
     */
    public function testReadsAndClaimFromConsumerGroupFromSingleStream(): void
    {
        $redis = $this->getClient();

        $streamInitId = $redis->xadd('stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', $streamInitId));

        $nextId = $redis->xadd('stream', ['newField' => 'newValue']);

        $expectedResponse = [
            [
                'stream',
                [
                    [$nextId, ['newField', 'newValue'], '0', '0'],
                ],
            ],
        ];

        $this->assertEmpty(
            $redis->xpending('stream', 'group', null, '-', '+', 5)
        );

        $this->assertEquals(
            $expectedResponse,
            $redis->xreadgroup_claim(
                'group',
                'consumer',
                ['stream' => '>'], null, null, false, 10)
        );

        $this->assertCount(
            4,
            $redis->xpending('stream', 'group', null, '-', '+', 5)[0]
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.3.224
     */
    public function testReadsAndClaimFromConsumerGroupFromMultipleStreams(): void
    {
        $redis = $this->getClient();

        $streamInitId = $redis->xadd('stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', $streamInitId));

        $anotherStreamInitId = $redis->xadd('another_stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('another_stream', 'group', $anotherStreamInitId));

        $nextId = $redis->xadd('stream', ['newField' => 'newValue']);
        $anotherNextId = $redis->xadd('another_stream', ['newField' => 'newValue']);

        $expectedResponse = [
            [
                'stream',
                [
                    [$nextId, ['newField', 'newValue'], '0', '0'],
                ],
            ],
            [
                'another_stream',
                [
                    [$anotherNextId, ['newField', 'newValue'], '0', '0'],
                ],
            ],
        ];

        $this->assertEmpty(
            $redis->xpending('stream', 'group', null, '-', '+', 5)
        );
        $this->assertEmpty(
            $redis->xpending('another_stream', 'group', null, '-', '+', 5)
        );

        $this->assertEquals(
            $expectedResponse,
            $redis->xreadgroup_claim(
                'group',
                'consumer',
                ['stream' => '>', 'another_stream' => '>'], null, null, false, 10)
        );

        $this->assertCount(
            4,
            $redis->xpending('stream', 'group', null, '-', '+', 5)[0]
        );
        $this->assertCount(
            4,
            $redis->xpending('another_stream', 'group', null, '-', '+', 5)[0]
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadsWithMaxCountCapsCumulativeEntriesAcrossStreams(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', '0', true));
        $this->assertEquals('OK', $redis->xgroup->create('another_stream', 'group', '0', true));

        $redis->xadd('stream', ['field' => 'value1'], '1-0');
        $redis->xadd('stream', ['field' => 'value2'], '2-0');
        $redis->xadd('another_stream', ['field' => 'value3'], '1-0');
        $redis->xadd('another_stream', ['field' => 'value4'], '2-0');

        // MAXCOUNT caps the cumulative reply across all streams, streams are
        // served in caller order and remaining streams are skipped.
        $expectedResponse = [
            [
                'stream',
                [
                    ['1-0', ['field', 'value1']],
                    ['2-0', ['field', 'value2']],
                ],
            ],
            [
                'another_stream',
                [
                    ['1-0', ['field', 'value3']],
                ],
            ],
        ];

        $this->assertSame(
            $expectedResponse,
            $redis->xreadgroup_claim(
                'group',
                'consumer',
                ['stream' => '>', 'another_stream' => '>'],
                2,
                null,
                false,
                null,
                3
            )
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadsWithMaxSizeAllowsFirstOversizedEntry(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', '0', true));
        $this->assertEquals('OK', $redis->xgroup->create('another_stream', 'group', '0', true));

        $redis->xadd('stream', ['field' => 'value1'], '1-0');
        $redis->xadd('stream', ['field' => 'value2'], '2-0');
        $redis->xadd('another_stream', ['field' => 'value3'], '1-0');

        // MAXSIZE is a soft byte cap: the first available entry is returned
        // even when it alone exceeds the budget, remaining streams are skipped.
        $expectedResponse = [
            [
                'stream',
                [
                    ['1-0', ['field', 'value1']],
                ],
            ],
        ];

        $this->assertSame(
            $expectedResponse,
            $redis->xreadgroup_claim(
                'group',
                'consumer',
                ['stream' => '>', 'another_stream' => '>'],
                null,
                null,
                false,
                null,
                null,
                1
            )
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReadsWithMaxCountAndMaxSizeResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', '0', true));

        $redis->xadd('stream', ['field' => 'value1'], '1-0');
        $redis->xadd('stream', ['field' => 'value2'], '2-0');

        // RESP3 replies with a native map keyed by stream name.
        $expectedResponse = [
            'stream' => [
                ['1-0', ['field', 'value1']],
            ],
        ];

        $this->assertEquals(
            $expectedResponse,
            $redis->xreadgroup_claim(
                'group',
                'consumer',
                ['stream' => '>'],
                null,
                null,
                false,
                null,
                1,
                1048576
            )
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testThrowsExceptionOnMaxCountLowerThanCount(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', '0', true));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('MAXCOUNT must be greater than or equal to COUNT');

        $redis->xreadgroup_claim('group', 'consumer', ['stream' => '>'], 2, null, false, null, 1);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['group', 'consumer', ['stream' => '0-0']],
                ['GROUP', 'group', 'consumer', 'STREAMS', 'stream', '0-0'],
            ],
            'with COUNT modifier' => [
                ['group', 'consumer', ['stream' => '0-0'], 10],
                ['GROUP', 'group', 'consumer', 'COUNT', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with BLOCK modifier' => [
                ['group', 'consumer', ['stream' => '0-0'], null, 10],
                ['GROUP', 'group', 'consumer', 'BLOCK', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with NOACK modifier' => [
                ['group', 'consumer', ['stream' => '0-0'], null, null, true],
                ['GROUP', 'group', 'consumer', 'NOACK', 'STREAMS', 'stream', '0-0'],
            ],
            'with CLAIM modifier' => [
                ['group', 'consumer', ['stream' => '0-0'], null, null, false, 10],
                ['GROUP', 'group', 'consumer', 'CLAIM', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with all arguments' => [
                ['group', 'consumer', ['stream' => '0-0', 'stream1' => '0-0'], 10, 10, true, 20],
                ['GROUP', 'group', 'consumer', 'COUNT', 10, 'BLOCK', 10, 'NOACK', 'CLAIM', 20, 'STREAMS', 'stream',  'stream1', '0-0', '0-0'],
            ],
            'with MAXCOUNT modifier' => [
                ['group', 'consumer', ['stream' => '0-0'], null, null, false, null, 10],
                ['GROUP', 'group', 'consumer', 'MAXCOUNT', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with MAXSIZE modifier' => [
                ['group', 'consumer', ['stream' => '0-0'], null, null, false, null, null, 65536],
                ['GROUP', 'group', 'consumer', 'MAXSIZE', 65536, 'STREAMS', 'stream', '0-0'],
            ],
            'with COUNT, MAXCOUNT and MAXSIZE modifiers' => [
                ['group', 'consumer', ['stream' => '0-0'], 10, null, false, null, 20, 65536],
                ['GROUP', 'group', 'consumer', 'COUNT', 10, 'MAXCOUNT', 20, 'MAXSIZE', 65536, 'STREAMS', 'stream', '0-0'],
            ],
            'with all arguments - including MAXCOUNT and MAXSIZE' => [
                ['group', 'consumer', ['stream' => '0-0', 'stream1' => '0-0'], 10, 10, true, 20, 30, 65536],
                ['GROUP', 'group', 'consumer', 'COUNT', 10, 'MAXCOUNT', 30, 'MAXSIZE', 65536, 'BLOCK', 10, 'NOACK', 'CLAIM', 20, 'STREAMS', 'stream',  'stream1', '0-0', '0-0'],
            ],
        ];
    }
}
