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

/**
 * @group commands
 * @group realm-server
 */
class TIME_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\TIME';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'TIME';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = [];
        $expected = [];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $expected = [1331114908, 453990];
        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($expected));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testReturnsServerTime(): void
    {
        $redis = $this->getClient();

        $this->assertIsArray($time = $redis->time());
        $this->assertIsString($time[0]);
        $this->assertIsString($time[1]);
    }
}
