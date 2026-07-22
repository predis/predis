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

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class TSQUERYLABELS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSQUERYLABELS::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSQUERYLABELS';
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
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testQueryReturnsLabelNamesMatchingGivenFilterExpression(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));

        $anotherArguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'JER');

        $this->assertEquals('OK', $redis->tscreate('temp:JER', $anotherArguments));

        $response = $redis->tsquerylabels(null, 'type=temp');
        sort($response);

        $this->assertSame(['location', 'type'], $response);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testQueryReturnsLabelValuesMatchingGivenFilterExpression(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));

        $anotherArguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'JER');

        $this->assertEquals('OK', $redis->tscreate('temp:JER', $anotherArguments));

        $response = $redis->tsquerylabels('location', 'type=temp');
        sort($response);

        $this->assertSame(['JER', 'TLV'], $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testQueryReturnsLabelNamesMatchingGivenFilterExpressionResp3(): void
    {
        $redis = $this->getResp3Client();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));

        $anotherArguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'JER');

        $this->assertEquals('OK', $redis->tscreate('temp:JER', $anotherArguments));

        $response = $redis->tsquerylabels(null, 'type=temp');
        sort($response);

        $this->assertSame(['location', 'type'], $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testQueryReturnsLabelValuesMatchingGivenFilterExpressionResp3(): void
    {
        $redis = $this->getResp3Client();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));

        $anotherArguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'JER');

        $this->assertEquals('OK', $redis->tscreate('temp:JER', $anotherArguments));

        $response = $redis->tsquerylabels('location', 'type=temp');
        sort($response);

        $this->assertSame(['JER', 'TLV'], $response);
    }

    public function argumentsProvider(): array
    {
        return [
            'with no arguments' => [
                [],
                ['LABELS'],
            ],
            'with null label' => [
                [null],
                ['LABELS'],
            ],
            'with null label and filter expressions' => [
                [null, 'type=temp', 'location!='],
                ['LABELS', 'FILTER', 'type=temp', 'location!='],
            ],
            'with label' => [
                ['location'],
                ['VALUES', 'location'],
            ],
            'with label and filter expressions' => [
                ['location', 'type=temp', 'location!='],
                ['VALUES', 'location', 'FILTER', 'type=temp', 'location!='],
            ],
        ];
    }
}
