<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Response\ServerException;

class XAUTOCLAIM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return XAUTOCLAIM::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'XAUTOCLAIM';
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
     * @requiresRedisVersion >= 7.0.0
     */
    public function testTransferStreamOwnershipToGivenConsumerGroup(): void
    {
        $redis = $this->getClient();

        $entryId = $redis->xadd('stream', ['field' => 'value']);
        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', $entryId));

        $nextEntryId = $redis->xadd('stream', ['newField' => 'newValue']);

        $redis->xreadgroup(
            'group',
            'consumer',
            null,
            null,
            false,
            'stream',
            '>');

        $expectedResponse = [
            '0-0',
            [
                [
                    $nextEntryId,
                    ['newField', 'newValue'],
                ],
            ],
            [],
        ];

        $this->assertSame(
            $expectedResponse,
            $redis->xautoclaim('stream', 'group', 'another_consumer', 0, $entryId)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnNonExistingConsumerGroupOrStream(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage(
            "NOGROUP No such key 'stream' or consumer group 'group'"
        );

        $redis->xautoclaim('stream', 'group', 'another_consumer', 0, '0-0');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 'group', 'consumer', 10000, '0-0'],
                ['key', 'group', 'consumer', 10000, '0-0'],
            ],
            'with COUNT modifier' => [
                ['key', 'group', 'consumer', 10000, '0-0', 20],
                ['key', 'group', 'consumer', 10000, '0-0', 'COUNT', 20],
            ],
            'with JUSTID modifier' => [
                ['key', 'group', 'consumer', 10000, '0-0', null, true],
                ['key', 'group', 'consumer', 10000, '0-0', 'JUSTID'],
            ],
            'with all arguments' => [
                ['key', 'group', 'consumer', 10000, '0-0', 10, true],
                ['key', 'group', 'consumer', 10000, '0-0', 'COUNT', 10, 'JUSTID'],
            ],
        ];
    }
}
