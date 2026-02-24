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

namespace Predis\Command\Redis;

use Predis\Command\Container\HOTKEYS as Container;
use Predis\NotSupportedException;
use ValueError;

/**
 * @group commands
 * @group relay-incompatible
 * @group realm-generic
 */
class HOTKEYS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return HOTKEYS::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HOTKEYS';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
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
     * @requiresRedisVersion >= 8.5.0
     * @return void
     */
    public function testRetrieveHotKeys()
    {
        $redis = $this->getClient();

        // Starts hotkeys tracking (CPU only)
        $this->assertEquals('OK', $redis->hotkeys->start([Container::CPU]));
        $this->assertEquals('OK', $redis->set('key', 'value'));
        $this->assertEquals('OK', $redis->hotkeys->stop());

        $hotkeysInfo = $redis->hotkeys->get()[0];
        $this->assertContains('key', $hotkeysInfo['by-cpu-time-us']);

        // Starts hotkeys tracking (CPU and NET)
        $this->assertEquals('OK', $redis->hotkeys->start([Container::CPU, Container::NET]));
        $this->assertEquals('OK', $redis->set('key', 'value'));
        $this->assertEquals('OK', $redis->set('key1', 'value1'));
        $this->assertEquals('OK', $redis->hotkeys->stop());

        $hotkeysInfo = $redis->hotkeys->get()[0];

        $this->assertContains('key', $hotkeysInfo['by-cpu-time-us']);
        $this->assertContains('key1', $hotkeysInfo['by-cpu-time-us']);
        $this->assertContains('key', $hotkeysInfo['by-net-bytes']);
        $this->assertContains('key1', $hotkeysInfo['by-net-bytes']);

        // Starts hotkeys tracking (limited COUNT)
        $this->assertEquals('OK', $redis->hotkeys->start([Container::CPU, Container::NET], 12));

        for ($i = 0; $i < 13; $i++) {
            $redis->set("key:$i", "value:$i");
        }
        $this->assertEquals('OK', $redis->hotkeys->stop());

        $hotkeysInfo = $redis->hotkeys->get()[0];
        $this->assertCount(24, $hotkeysInfo['by-cpu-time-us']);

        // Starts hotkeys tracking (with DURATION, SAMPLE)
        $this->assertEquals(
            'OK',
            $redis->hotkeys->start([Container::CPU, Container::NET], null, 1, 10)
        );
        $this->sleep(1.2);

        $hotkeysInfo = $redis->hotkeys->get()[0];
        $this->assertEquals(0, $hotkeysInfo['tracking-active']);
        $this->assertEquals(10, $hotkeysInfo['sample-ratio']);

        $this->assertEquals('OK', $redis->hotkeys->reset());

        $hotkeysInfo = $redis->hotkeys->get()[0];
        $this->assertEquals(0, $hotkeysInfo['tracking-active']);
        $this->assertNull($hotkeysInfo['sample-ratio']);
        $this->assertEmpty($hotkeysInfo['selected-slots']);
        $this->assertEmpty($hotkeysInfo['by-cpu-time-us']);
        $this->assertEmpty($hotkeysInfo['by-net-bytes']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.5.0
     * @return void
     */
    public function testRetrieveHotKeysResp3()
    {
        $redis = $this->getResp3Client();

        // Starts hotkeys tracking (CPU only)
        $this->assertEquals('OK', $redis->hotkeys->start([Container::CPU]));
        $this->assertEquals('OK', $redis->set('key', 'value'));
        $this->assertEquals('OK', $redis->hotkeys->stop());

        $hotkeysInfo = $redis->hotkeys->get()[0];
        $this->assertContains('key', $hotkeysInfo['by-cpu-time-us']);

        // Starts hotkeys tracking (CPU and NET)
        $this->assertEquals('OK', $redis->hotkeys->start([Container::CPU, Container::NET]));
        $this->assertEquals('OK', $redis->set('key', 'value'));
        $this->assertEquals('OK', $redis->set('key1', 'value1'));
        $this->assertEquals('OK', $redis->hotkeys->stop());

        $hotkeysInfo = $redis->hotkeys->get()[0];

        $this->assertContains('key', $hotkeysInfo['by-cpu-time-us']);
        $this->assertContains('key1', $hotkeysInfo['by-cpu-time-us']);
        $this->assertContains('key', $hotkeysInfo['by-net-bytes']);
        $this->assertContains('key1', $hotkeysInfo['by-net-bytes']);

        // Starts hotkeys tracking (limited COUNT)
        $this->assertEquals('OK', $redis->hotkeys->start([Container::CPU, Container::NET], 12));

        for ($i = 0; $i < 13; $i++) {
            $redis->set("key:$i", "value:$i");
        }
        $this->assertEquals('OK', $redis->hotkeys->stop());

        $hotkeysInfo = $redis->hotkeys->get()[0];
        $this->assertCount(24, $hotkeysInfo['by-cpu-time-us']);

        // Starts hotkeys tracking (with DURATION, SAMPLE)
        $this->assertEquals(
            'OK',
            $redis->hotkeys->start([Container::CPU, Container::NET], null, 1, 10)
        );
        $this->sleep(1.2);

        $hotkeysInfo = $redis->hotkeys->get()[0];
        $this->assertEquals(0, $hotkeysInfo['tracking-active']);
        $this->assertEquals(10, $hotkeysInfo['sample-ratio']);

        $this->assertEquals('OK', $redis->hotkeys->reset());

        $hotkeysInfo = $redis->hotkeys->get()[0];
        $this->assertEquals(0, $hotkeysInfo['tracking-active']);
        $this->assertNull($hotkeysInfo['sample-ratio']);
        $this->assertEmpty($hotkeysInfo['selected-slots']);
        $this->assertEmpty($hotkeysInfo['by-cpu-time-us']);
        $this->assertEmpty($hotkeysInfo['by-net-bytes']);
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.5.0
     * @return void
     */
    public function testHotkeysStartDisabledInClusterClient()
    {
        $redis = $this->getClient();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage("Cannot use 'HOTKEYS' with redis-cluster");

        $redis->hotkeys->start([Container::CPU]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidSampleValue(): void
    {
        $command = $this->getCommand();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Sample value should be greater than 0');

        $command->setArguments(['START', ['metric1', 'metric2'], null, null, 0]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNegativeSampleValue(): void
    {
        $command = $this->getCommand();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Sample value should be greater than 0');

        $command->setArguments(['START', ['metric1', 'metric2'], null, null, -1]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnCountValueTooLow(): void
    {
        $command = $this->getCommand();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Count value should be between 10 and 64');

        $command->setArguments(['START', ['metric1', 'metric2'], 9]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnCountValueTooHigh(): void
    {
        $command = $this->getCommand();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Count value should be between 10 and 64');

        $command->setArguments(['START', ['metric1', 'metric2'], 65]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnCountValueZero(): void
    {
        $command = $this->getCommand();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Count value should be between 10 and 64');

        $command->setArguments(['START', ['metric1', 'metric2'], 0]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with non-START subcommand' => [
                ['STOP'],
                ['STOP'],
            ],
            'with START and metrics only' => [
                ['START', ['metric1', 'metric2']],
                ['START', 'METRICS', 2, 'metric1', 'metric2'],
            ],
            'with START, metrics and COUNT' => [
                ['START', ['metric1', 'metric2'], 50],
                ['START', 'METRICS', 2, 'metric1', 'metric2', 'COUNT', 50],
            ],
            'with START, metrics, COUNT and DURATION' => [
                ['START', ['metric1', 'metric2'], 50, 60],
                ['START', 'METRICS', 2, 'metric1', 'metric2', 'COUNT', 50, 'DURATION', 60],
            ],
            'with START, metrics, COUNT, DURATION and SAMPLE' => [
                ['START', ['metric1', 'metric2'], 50, 60, 10],
                ['START', 'METRICS', 2, 'metric1', 'metric2', 'COUNT', 50, 'DURATION', 60, 'SAMPLE', 10],
            ],
            'with START, metrics, COUNT, DURATION, SAMPLE and SLOTS' => [
                ['START', ['metric1', 'metric2'], 50, 60, 10, [1, 2, 3]],
                ['START', 'METRICS', 2, 'metric1', 'metric2', 'COUNT', 50, 'DURATION', 60, 'SAMPLE', 10, 'SLOTS', 3, 1, 2, 3],
            ],
            'with START, metrics and SLOTS (no COUNT, DURATION, SAMPLE)' => [
                ['START', ['metric1'], null, null, null, [5, 10]],
                ['START', 'METRICS', 1, 'metric1', 'SLOTS', 2, 5, 10],
            ],
            'with START, metrics, COUNT and SLOTS (no DURATION, SAMPLE)' => [
                ['START', ['metric1'], 50, null, null, [1, 2]],
                ['START', 'METRICS', 1, 'metric1', 'COUNT', 50, 'SLOTS', 2, 1, 2],
            ],
            'with START, metrics, COUNT, DURATION and SLOTS (no SAMPLE)' => [
                ['START', ['metric1'], 50, 30, null, [1]],
                ['START', 'METRICS', 1, 'metric1', 'COUNT', 50, 'DURATION', 30, 'SLOTS', 1, 1],
            ],
            'with START and single metric' => [
                ['START', ['metric1']],
                ['START', 'METRICS', 1, 'metric1'],
            ],
            'with START and multiple metrics' => [
                ['START', ['metric1', 'metric2', 'metric3']],
                ['START', 'METRICS', 3, 'metric1', 'metric2', 'metric3'],
            ],
            'with START, metrics and minimum valid COUNT (10)' => [
                ['START', ['metric1'], 10],
                ['START', 'METRICS', 1, 'metric1', 'COUNT', 10],
            ],
            'with START, metrics and maximum valid COUNT (64)' => [
                ['START', ['metric1'], 64],
                ['START', 'METRICS', 1, 'metric1', 'COUNT', 64],
            ],
        ];
    }
}
