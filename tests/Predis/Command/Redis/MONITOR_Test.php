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
 * @group realm-monitor
 */
class MONITOR_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MONITOR';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MONITOR';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments([]);

        $this->assertSame([], $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     */
    public function testReturnsStatusResponseAndReadsEventsFromTheConnection(): void
    {
        $connection = $this->getClient()->getConnection();
        $command = $this->getCommand();

        $this->assertEquals('OK', $connection->executeCommand($command));

        // NOTE: Starting with 2.6 Redis does not return the "MONITOR" message after
        // +OK to the client that issued the MONITOR command.
        if ($this->isRedisServerVersion('<=', '2.4.0')) {
            $this->assertMatchesRegularExpression('/\d+.\d+(\s?\(db \d+\))? "MONITOR"/', $connection->read());
        }
    }
}
