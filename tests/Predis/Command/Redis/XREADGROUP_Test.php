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

class XREADGROUP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return XREADGROUP::class;
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
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @group relay-incompatible
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
            $redis->xreadgroup(
                'group',
                'consumer',
                null,
                null,
                false,
                'stream',
                '>')
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
            $redis->xreadgroup(
                'group',
                'consumer',
                null,
                null,
                false,
                'stream',
                'another_stream',
                '>',
                '>'
            )
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testThrowsExceptionOnNonExistingConsumerGroupOrStream(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage(
            "NOGROUP No such key 'stream' or consumer group 'group' in XREADGROUP with GROUP option"
        );

        $redis->xreadgroup(
            'group',
            'consumer',
            null,
            null,
            false,
            'stream',
            '>');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['group', 'consumer', null, null, false, 'stream', '0-0'],
                ['GROUP', 'group', 'consumer', 'STREAMS', 'stream', '0-0'],
            ],
            'with COUNT modifier' => [
                ['group', 'consumer', 10, null, false, 'stream', '0-0'],
                ['GROUP', 'group', 'consumer', 'COUNT', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with BLOCK modifier' => [
                ['group', 'consumer', null, 10, false, 'stream', '0-0'],
                ['GROUP', 'group', 'consumer', 'BLOCK', 10, 'STREAMS', 'stream', '0-0'],
            ],
            'with NOACK modifier' => [
                ['group', 'consumer', null, null, true, 'stream', '0-0'],
                ['GROUP', 'group', 'consumer', 'NOACK', 'STREAMS', 'stream', '0-0'],
            ],
            'with all arguments' => [
                ['group', 'consumer', 10, 10, true, 'stream', '0-0', '10-0'],
                ['GROUP', 'group', 'consumer', 'COUNT', 10, 'BLOCK', 10, 'NOACK', 'STREAMS', 'stream', '0-0', '10-0'],
            ],
        ];
    }
}
