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

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\SugAddArguments;
use Predis\Command\Argument\Search\SugGetArguments;
use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class FTSUGGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTSUGGET::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTSUGGET';
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
     * @group relay-incompatible
     * @dataProvider suggestionProvider
     * @param  array $addArguments
     * @param  array $getArguments
     * @param  array $expectedResponse
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testGetSuggestionsForGivenPrefix(
        array $addArguments,
        array $getArguments,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->ftsugadd(...$addArguments);

        $actualResponse = $redis->ftsugget(...$getArguments);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testGetReturnsLimitedResultsWithMaxModifier(): void
    {
        $redis = $this->getClient();

        $redis->ftsugadd('key', 'hello', 2);
        $redis->ftsugadd('key', 'hell', 2);

        $actualResponse = $redis->ftsugget('key', 'hel', (new SugGetArguments())->max(1));

        $this->assertSame(['hell'], $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testGetReturnsLimitedResultsWithMaxModifierResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->ftsugadd('key', 'hello', 2);
        $redis->ftsugadd('key', 'hell', 2);

        $actualResponse = $redis->ftsugget('key', 'hel', (new SugGetArguments())->max(1));

        $this->assertSame(['hell'], $actualResponse);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testGetReturnsNullOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->ftsugget('key', 'hel'));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 'prefix'],
                ['key', 'prefix'],
            ],
            'with FUZZY modifier' => [
                ['key', 'prefix', (new SugGetArguments())->fuzzy()],
                ['key', 'prefix', 'FUZZY'],
            ],
            'with WITHSCORES modifier' => [
                ['key', 'prefix', (new SugGetArguments())->withScores()],
                ['key', 'prefix', 'WITHSCORES'],
            ],
            'with WITHPAYLOADS modifier' => [
                ['key', 'prefix', (new SugGetArguments())->withPayloads()],
                ['key', 'prefix', 'WITHPAYLOADS'],
            ],
            'with MAX modifier' => [
                ['key', 'prefix', (new SugGetArguments())->max(5)],
                ['key', 'prefix', 'MAX', 5],
            ],
            'with all arguments' => [
                ['key', 'prefix', (new SugGetArguments())->fuzzy()->withScores()->withPayloads()->max(5)],
                ['key', 'prefix', 'FUZZY', 'WITHSCORES', 'WITHPAYLOADS', 'MAX', 5],
            ],
        ];
    }

    public function suggestionProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 'hello', 2],
                ['key', 'hell'],
                ['hello'],
            ],
            'with FUZZY search' => [
                ['key', 'hello', 2],
                ['key', 'help', (new SugGetArguments())->fuzzy()],
                ['hello'],
            ],
            'with WITHSCORES modifier' => [
                ['key', 'hello', 2],
                ['key', 'hell', (new SugGetArguments())->withScores()],
                ['hello', '1.4142135381698608'],
            ],
            'with WITHPAYLOADS modifier' => [
                ['key', 'hello', 2, (new SugAddArguments())->payload('payload')],
                ['key', 'hell', (new SugGetArguments())->withPayloads()],
                ['hello', 'payload'],
            ],
            'with all modifiers' => [
                ['key', 'hello', 2, (new SugAddArguments())->payload('payload')],
                ['key', 'hellp', (new SugGetArguments())->fuzzy()->withScores()->withPayloads()],
                ['hello', '290630304', 'payload'],
            ],
        ];
    }
}
