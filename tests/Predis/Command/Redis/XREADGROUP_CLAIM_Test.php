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
        ];
    }
}
