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

use Predis\Command\Argument\TimeSeries\BGetArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSBGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSBGET::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSBGET';
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
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1'];

        $command->setRawArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.8.0
     */
    public function testRetrievesAvailableSamplesImmediatelyWhenTimeoutIsZero(): void
    {
        $redis = $this->getClient();

        $createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $createArguments));

        $this->assertSame(
            [1000, 1010, 1020],
            $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 40)
        );

        $this->assertEquals(
            [[1000, '30'], [1010, '35'], [1020, '40']],
            $redis->tsbget('temp:TLV', '-', 0)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.8.0
     */
    public function testRetrievesAvailableSamplesImmediatelyWhenTimeoutIsZeroResp3(): void
    {
        $redis = $this->getResp3Client();

        $createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $createArguments));

        $this->assertSame(
            [1000, 1010, 1020],
            $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 40)
        );

        $this->assertEquals(
            [[1000, '30'], [1010, '35'], [1020, '40']],
            $redis->tsbget('temp:TLV', '-', 0)
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsMinCountSamplesImmediatelyWhenAvailable(): void
    {
        $redis = $this->getClient();

        $createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $createArguments));

        $this->assertSame(
            [1000, 1010, 1020],
            $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 40)
        );

        // At least 2 samples are available, so the command returns without blocking.
        $arguments = (new BGetArguments())->minCount(2);

        $this->assertEquals(
            [[1000, '30'], [1010, '35'], [1020, '40']],
            $redis->tsbget('temp:TLV', '-', 5000, $arguments)
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsNoMoreThanMaxCountSamples(): void
    {
        $redis = $this->getClient();

        $createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $createArguments));

        $this->assertSame(
            [1000, 1010, 1020, 1030],
            $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 40, 'temp:TLV', 1030, 45)
        );

        $arguments = (new BGetArguments())->maxCount(2);

        $this->assertEquals(
            [[1000, '30'], [1010, '35']],
            $redis->tsbget('temp:TLV', '-', 0, $arguments)
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsEmptyListWhenNoSamplesAvailable(): void
    {
        $redis = $this->getClient();

        $createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $createArguments));

        // No samples have been added, with timeout 0 the command returns immediately.
        $this->assertSame([], $redis->tsbget('temp:TLV', '-', 0));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.8.0
     */
    public function testBlocksForTimeoutWhenMinCountNotReached(): void
    {
        $redis = $this->getClient();

        $createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $createArguments));

        $this->assertSame([1000], $redis->tsmadd('temp:TLV', 1000, 30));

        // Request more samples than available, the command should block for
        // the whole timeout before returning the available samples.
        $arguments = (new BGetArguments())->minCount(5);
        $timeout = 200;

        $start = microtime(true);
        $response = $redis->tsbget('temp:TLV', '-', $timeout, $arguments);
        $elapsedMs = (microtime(true) - $start) * 1000;

        $this->assertGreaterThanOrEqual($timeout, $elapsedMs);
        $this->assertLessThan(5, count($response));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', '-', 0],
                ['key', '-', 0],
            ],
            'with numeric timestamp' => [
                ['key', 1000, 500],
                ['key', 1000, 500],
            ],
            'with MIN_COUNT modifier' => [
                ['key', '-', 0, (new BGetArguments())->minCount(5)],
                ['key', '-', 0, 'MIN_COUNT', 5],
            ],
            'with MAX_COUNT modifier' => [
                ['key', '-', 0, (new BGetArguments())->maxCount(10)],
                ['key', '-', 0, 'MAX_COUNT', 10],
            ],
            'with all modifiers' => [
                ['key', '$', 1000, (new BGetArguments())->minCount(5)->maxCount(10)],
                ['key', '$', 1000, 'MIN_COUNT', 5, 'MAX_COUNT', 10],
            ],
        ];
    }
}
