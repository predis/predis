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
            foreach (['name', 'pending', 'idle'] as $key) {
                $this->assertArrayHasKey($key, $consumer);
            }
        }
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsConsumerGroupsOfGivenStream(): void
    {
        $redis = $this->getClient();

        $entityId = $redis->xadd('stream', ['field' => 'value']);

        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group1', $entityId));
        $this->assertEquals('OK', $redis->xgroup->create('stream', 'group2', $entityId));

        $expectedResponse = [
            [
                'name' => 'group1',
                'consumers' => 0,
                'pending' => 0,
                'last-delivered-id' => $entityId,
                'entries-read' => null,
                'lag' => 0,
            ],
            [
                'name' => 'group2',
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
     * @requiresRedisVersion >= 7.0.0
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

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.2.0
     */
    public function testReturnsInformationAboutGivenStreamExtended(): void
    {
        $redis = $this->getClient();

        $redis->xadd('key', ['k' => 'v'], '1-0');
        $redis->xadd('key', ['k' => 'v'], '1-1');
        $redis->xadd('key', ['k' => 'v'], '1-2');
        $redis->xgroup->create('key', 'group1', '0');
        $redis->xgroup->create('key', 'group2', '0');
        $redis->xgroup->createConsumer('key', 'group1', 'consumer1');
        $redis->xgroup->createConsumer('key', 'group1', 'consumer2');
        $redis->xgroup->createConsumer('key', 'group2', 'consumer1');
        $redis->xgroup->createConsumer('key', 'group2', 'consumer2');
        $redis->xreadgroup('group1', 'consumer1', 2, null, false, 'key', '>');
        $redis->xreadgroup('group2', 'consumer2', 2, null, false, 'key', '>');
        $redis->xdel('key', '1-1');

        $result = $redis->xinfo->stream('key', (new XInfoStreamOptions())->full(10));
        $expectedResponse = [
            'length' => 2,
            'radix-tree-keys' => 1,
            'radix-tree-nodes' => 2,
            'last-generated-id' => '1-2',
            'max-deleted-entry-id' => '1-1',
            'entries-added' => 3,
            'recorded-first-entry-id' => '1-0',
            'entries' => [['1-0' => ['k' => 'v']], ['1-2' => ['k' => 'v']]],
            'groups' => [
                [
                    'name' => 'group1',
                    'last-delivered-id' => '1-1',
                    'entries-read' => 2,
                    'lag' => null,
                    'pel-count' => 2,
                    'pending' => [
                        ['1-0', 'consumer1', $result['groups'][0]['pending'][0][2] ?? 0, 1],
                        ['1-1', 'consumer1', $result['groups'][0]['pending'][1][2] ?? 0, 1],
                    ],
                    'consumers' => [
                        [
                            'name' => 'consumer1',
                            'seen-time' => $result['groups'][0]['consumers'][0]['seen-time'] ?? 0,
                            'active-time' => $result['groups'][0]['consumers'][0]['active-time'] ?? 0,
                            'pel-count' => 2,
                            'pending' => [
                                ['1-0', $result['groups'][0]['consumers'][0]['pending'][0][1] ?? 0, 1],
                                ['1-1', $result['groups'][0]['consumers'][0]['pending'][1][1] ?? 0, 1],
                            ],
                        ],
                        [
                            'name' => 'consumer2',
                            'seen-time' => $result['groups'][0]['consumers'][1]['seen-time'] ?? 0,
                            'active-time' => $result['groups'][0]['consumers'][1]['active-time'] ?? 0,
                            'pel-count' => 0,
                            'pending' => [],
                        ],
                    ],
                ],
                [
                    'name' => 'group2',
                    'last-delivered-id' => '1-1',
                    'entries-read' => 2,
                    'lag' => null,
                    'pel-count' => 2,
                    'pending' => [
                        ['1-0', 'consumer2', $result['groups'][1]['pending'][0][2] ?? 0, 1],
                        ['1-1', 'consumer2', $result['groups'][1]['pending'][0][2] ?? 0, 1],
                    ],
                    'consumers' => [
                        [
                            'name' => 'consumer1',
                            'seen-time' => $result['groups'][1]['consumers'][0]['seen-time'] ?? 0,
                            'active-time' => $result['groups'][1]['consumers'][0]['active-time'] ?? 0,
                            'pel-count' => 0,
                            'pending' => [],
                        ],
                        [
                            'name' => 'consumer2',
                            'seen-time' => $result['groups'][1]['consumers'][1]['seen-time'] ?? 0,
                            'active-time' => $result['groups'][1]['consumers'][1]['active-time'] ?? 0,
                            'pel-count' => 2,
                            'pending' => [
                                ['1-0', $result['groups'][1]['consumers'][1]['pending'][0][1] ?? 0, 1],
                                ['1-1', $result['groups'][1]['consumers'][1]['pending'][1][1] ?? 0, 1],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedResponse, $result);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testSameResponseResp2AndResp3(): void
    {
        $redis = $this->getClient();
        $redisResp3 = $this->getResp3Client();

        $redis->xadd('key', ['k' => 'v'], '1-0');
        $redis->xadd('key', ['k' => 'v'], '1-1');
        $redis->xadd('key', ['k' => 'v'], '1-2');
        $redis->xgroup->create('key', 'group1', '0');
        $redis->xgroup->create('key', 'group2', '0');
        $redis->xgroup->createConsumer('key', 'group1', 'consumer1');
        $redis->xgroup->createConsumer('key', 'group1', 'consumer2');
        $redis->xgroup->createConsumer('key', 'group2', 'consumer1');
        $redis->xgroup->createConsumer('key', 'group2', 'consumer2');
        $redis->xreadgroup('group1', 'consumer1', 2, null, false, 'key', '>');
        $redis->xreadgroup('group2', 'consumer2', 2, null, false, 'key', '>');
        $redis->xdel('key', '1-1');

        $resp2 = $redis->xinfo->consumers('key', 'group1');
        $resp3 = $redisResp3->xinfo->consumers('key', 'group1');
        $this->assertSameExceptKeys($resp2, $resp3, ['idle', 'inactive']);

        $resp2 = $redis->xinfo->groups('key');
        $resp3 = $redisResp3->xinfo->groups('key');
        $this->assertSame($resp2, $resp3);

        $resp2 = $redis->xinfo->stream('key', (new XInfoStreamOptions())->full(5));
        $resp3 = $redisResp3->xinfo->stream('key', (new XInfoStreamOptions())->full(5));
        $this->assertSame($resp2, $resp3);
    }

    private function assertSameExceptKeys(array $resp2, array $resp3, array $keys): void
    {
        $keys = array_flip($keys);
        array_map(function (array $el1, array $el2) use ($keys) {
            $this->assertSame(
                array_diff_key($el1, $keys),
                array_diff_key($el2, $keys)
            );
        }, $resp2, $resp3);
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
                [
                    ['name', 'consumer1', 'pending', 0, 'idle', 3, 'inactive', -1],
                    ['name', 'consumer2', 'pending', 1, 'idle', 5, 'inactive', -1],
                ],
                [
                    ['name' => 'consumer1', 'pending' => 0, 'idle' => 3, 'inactive' => -1],
                    ['name' => 'consumer2', 'pending' => 1, 'idle' => 5, 'inactive' => -1],
                ],
            ],
            'GROUPS response' => [
                ['GROUPS'],
                [
                    ['name', 'group1', 'consumers', 0, 'pending', 0, 'last-delivered-id', 3],
                    ['name', 'group2', 'consumers', 0, 'pending', 0, 'last-delivered-id', 3],
                ],
                [
                    ['name' => 'group1', 'consumers' => 0, 'pending' => 0, 'last-delivered-id' => 3],
                    ['name' => 'group2', 'consumers' => 0, 'pending' => 0, 'last-delivered-id' => 3],
                ],
            ],
            'STREAM response' => [
                ['STREAM', 'key'],
                ['length', 1, 'entries-added', 1, 'entries', [['id', ['field', 'value']]]],
                ['length' => 1, 'entries-added' => 1, 'entries' => [['id' => ['field' => 'value']]]],
            ],
        ];
    }
}
