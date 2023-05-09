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
 * @group realm-key
 */
class MIGRATE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MIGRATE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MIGRATE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['127.0.0.1', '6379', 'key', '0', '10'];
        $expected = ['127.0.0.1', '6379', 'key', '0', '10'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsRedis300(): void
    {
        $arguments = ['127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE'];
        $expected = ['127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithOptionsArray(): void
    {
        $arguments = ['127.0.0.1', '6379', 'key', '0', '10', ['COPY' => true, 'REPLACE' => true]];
        $expected = ['127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame('OK', $command->parseResponse('OK'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 2.6.0
     */
    public function testReturnsStatusNOKEYOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('NOKEY', $response = $redis->migrate('169.254.10.10', 16379, 'foo', 15, 1));
        $this->assertInstanceOf('Predis\Response\Status', $response);
    }

    /**
     * @group connected
     * @group ext-relay
     * @requiresRedisVersion >= 2.6.0
     */
    public function testReturnsStatusNOKEYOnNonExistingKeyUsingRelay(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('NOKEY', $redis->migrate('169.254.10.10', 16379, 'foo', 15, 1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     * @group slow
     */
    public function testReturnsErrorOnUnreacheableDestination(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('IOERR');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->migrate('169.254.10.10', 16379, 'foo', 15, 1);
    }
}
