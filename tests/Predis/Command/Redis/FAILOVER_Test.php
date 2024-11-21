<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Argument\Server\To;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-server
 */
class FAILOVER_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return FAILOVER::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'FAILOVER';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
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
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testPerformFailoverOfConnectedReplica(): void
    {
        $this->markTestSkipped('Test requires configured replica node connected to master');
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $redis = $this->getClient();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong timeout argument value or position offset');

        $redis->failover(null, false, 0);
    }

    public function argumentsProvider(): array
    {
        return [
            'without optional arguments - no arguments' => [
                [],
                [],
            ],
            'without optional arguments - default arguments' => [
                [null, false, -1],
                [],
            ],
            'with TO argument - no FORCE' => [
                [new To('test', 9999)],
                ['TO', 'test', 9999],
            ],
            'with TO argument - with FORCE' => [
                [new To('test', 9999, true)],
                ['TO', 'test', 9999, 'FORCE'],
            ],
            'with ABORT modifier' => [
                [null, true],
                ['ABORT'],
            ],
            'with TIMEOUT argument' => [
                [null, false, 10],
                ['TIMEOUT', 10],
            ],
            'with all arguments' => [
                [new To('test', 9999, true), true, 10],
                ['TO', 'test', 9999, 'FORCE', 'ABORT', 'TIMEOUT', 10],
            ],
        ];
    }
}
