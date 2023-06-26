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

use Predis\Command\Argument\Stream\XInfoStreamOptions;

class XINFO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return XINFO::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'XINFO';
    }

    /**
     * @group disconnected
     */
    public function testConsumersFilterArguments(): void
    {
        $arguments = ['CONSUMERS', 'key', 'group'];
        $expected = ['CONSUMERS', 'key', 'group'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGroupsFilterArguments(): void
    {
        $arguments = ['GROUPS', 'key'];
        $expected = ['GROUPS', 'key'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @dataProvider streamArgumentsProvider
     * @group disconnected
     */
    public function testStreamFilterArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedResponse, $command->getArguments());
    }

    /**
     * @dataProvider responseProvider
     * @group disconnected
     */
    public function testParseResponse(array $arguments, array $actualResponse, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expectedResponse, $command->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsConsumersOfGivenGroup(): void
    {
        $redis = $this->getClient();

        $entityId = $redis->xadd('stream', ['field' => 'value']);

        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', $entityId));
        $this->assertSame(1, $redis->xgroup->createConsumer('stream', 'group', 'consumer'));

        $response = $redis->xinfo->consumers('stream', 'group');

        foreach ($response as $consumer) {
            foreach (['name', 'pending', 'idle', 'inactive'] as $key) {
                $this->assertArrayHasKey($key, $consumer);
            }
        }
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsConsumerGroupsOfGivenStream(): void
    {
        $redis = $this->getClient();

        $entityId = $redis->xadd('stream', ['field' => 'value']);

        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group', $entityId));

        $expectedResponse = [
            [
                'name' => 'group',
                'consumers' => 0,
                'pending' => 0,
                'last-delivered-id' => $entityId,
                'entries-read' => null,
                'lag' => 0,
            ],
        ];

        $this->assertSame($expectedResponse, $redis->xinfo->groups('stream'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsInformationAboutGivenStream(): void
    {
        $redis = $this->getClient();

        $entityId = $redis->xadd('stream', ['field' => 'value']);
        $expectedResponse = [
            'length' => 1,
            'radix-tree-keys' => 1,
            'radix-tree-nodes' => 2,
            'last-generated-id' => $entityId,
            'max-deleted-entry-id' => '0-0',
            'entries-added' => 1,
            'recorded-first-entry-id' => $entityId,
            'entries' => [
                [
                    $entityId => ['field' => 'value'],
                ],
            ],
            'groups' => [],
        ];

        $options = new XInfoStreamOptions();
        $options->full(5);

        $this->assertSame($expectedResponse, $redis->xinfo->stream('stream', $options));
    }

    public function streamArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['STREAM', 'key'],
                ['STREAM', 'key'],
            ],
            'with FULL modifier - no COUNT' => [
                ['STREAM', 'key', (new XInfoStreamOptions())->full()],
                ['STREAM', 'key', 'FULL'],
            ],
            'with FULL modifier - with COUNT' => [
                ['STREAM', 'key', (new XInfoStreamOptions())->full(15)],
                ['STREAM', 'key', 'FULL', 'COUNT', 15],
            ],
        ];
    }

    public function responseProvider(): array
    {
        return [
            'CONSUMERS response' => [
                ['CONSUMERS'],
                [['name', 'consumer', 'pending', 0, 'idle', 3, 'inactive', -1]],
                [['name' => 'consumer', 'pending' => 0, 'idle' => 3, 'inactive' => -1]],
            ],
            'GROUPS response' => [
                ['GROUPS'],
                [['name', 'group', 'consumers', 0, 'pending', 0, 'last-delivered-id', 3]],
                [['name' => 'group', 'consumers' => 0, 'pending' => 0, 'last-delivered-id' => 3]],
            ],
            'STREAM response' => [
                ['STREAM', 'key'],
                [['length', 1, 'entries-added', 1, 'entries', [['id', ['field', 'value']]]]],
                [['length' => 1, 'entries-added' => 1, 'entries' => [['id' => ['field' => 'value']]]]],
            ],
        ];
    }
}
