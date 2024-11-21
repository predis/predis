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

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\SugAddArguments;
use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class FTSUGADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTSUGADD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTSUGADD';
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
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testAddSuggestionStringIntoAutoCompleteDictionary(): void
    {
        $redis = $this->getClient();

        $this->assertSame(1, $redis->ftsugadd('key', 'string', 1));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 'string', 1],
                ['key', 'string', 1],
            ],
            'with INCR modifier' => [
                ['key', 'string', 1, (new SugAddArguments())->incr()],
                ['key', 'string', 1, 'INCR'],
            ],
            'with PAYLOAD' => [
                ['key', 'string', 1, (new SugAddArguments())->payload('payload')],
                ['key', 'string', 1, 'PAYLOAD', 'payload'],
            ],
            'with all arguments' => [
                ['key', 'string', 1, (new SugAddArguments())->incr()->payload('payload')],
                ['key', 'string', 1, 'INCR', 'PAYLOAD', 'payload'],
            ],
        ];
    }
}
