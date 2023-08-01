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

use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class FTDICTDEL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTDICTDEL::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTDICTDEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['dict', 'foo', 'bar'];
        $expectedArguments = ['dict', 'foo', 'bar'];

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
     * @dataProvider dictionariesProvider
     * @param  array $addArguments
     * @param  array $deleteArguments
     * @param  int   $expectedResponse
     * @return void
     * @requiresRediSearchVersion >= 1.4.0
     */
    public function testRemovesTermsFromGivenDictionary(
        array $addArguments,
        array $deleteArguments,
        int $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->ftdictadd(...$addArguments);

        $this->assertSame($expectedResponse, $redis->ftdictdel(...$deleteArguments));
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testRemovesTermsFromGivenDictionaryResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->ftdictadd('dict', 'foo', 'bar');

        $this->assertSame(1, $redis->ftdictdel('dict', 'foo'));
    }

    public function dictionariesProvider(): array
    {
        return [
            'removes existing term' => [
                ['dict', 'foo', 'bar'],
                ['dict', 'foo'],
                1,
            ],
            'removes non-existing term' => [
                ['dict', 'foo', 'bar'],
                ['dict', 'baz'],
                0,
            ],
            'removes from non-existing dict' => [
                ['dict', 'foo', 'bar'],
                ['dict123', 'baz'],
                0,
            ],
        ];
    }
}
