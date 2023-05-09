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

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-connection
 */
class PING_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\PING';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PING';
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
        $this->assertSame('PONG', $this->getCommand()->parseResponse('PONG'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     */
    public function testAlwaysReturnsStatusResponse(): void
    {
        $redis = $this->getClient();
        $response = $redis->ping();

        $this->assertInstanceOf('Predis\Response\Status', $response);
        $this->assertEquals('PONG', $response);
    }

    /**
     * @group connected
     * @group ext-relay
     */
    public function testAlwaysReturnsResponseUsingRelay(): void
    {
        $redis = $this->getClient();

        $response = $redis->ping();
        $this->assertEquals('PONG', $response);

        $response = $redis->ping('HELLO');
        $this->assertSame('HELLO', $response);
    }
}
