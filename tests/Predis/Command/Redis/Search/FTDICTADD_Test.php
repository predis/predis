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
class FTDICTADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTDICTADD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTDICTADD';
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
     * @return void
     * @requiresRediSearchVersion >= 1.4.0
     */
    public function testAddTermsIntoGivenDictionary(): void
    {
        $redis = $this->getClient();

        $actualResponse = $redis->ftdictadd('dict', 'foo', 'bar');

        $this->assertSame(2, $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testAddTermsIntoGivenDictionaryResp3(): void
    {
        $redis = $this->getResp3Client();

        $actualResponse = $redis->ftdictadd('dict', 'foo', 'bar');

        $this->assertSame(2, $actualResponse);
    }
}
