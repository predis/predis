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
 * @group realm-stream
 */
class XREVRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XREVRANGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XREVRANGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['stream', '1-1', '0-1', 123];
        $expected = ['stream', '1-1', '0-1', 'COUNT', 123];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsNoCount(): void
    {
        $arguments = ['stream', '1-1', '0-1'];
        $expected = ['stream', '1-1', '0-1'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = [['0-1', ['key', 'val']]];
        $expected = ['0-1' => ['key' => 'val']];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseMultipleKeys(): void
    {
        $raw = [['0-1', ['key1', 'val1', 'key2', 'val2']]];
        $expected = ['0-1' => ['key1' => 'val1', 'key2' => 'val2']];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReturnsElementsInRange(): void
    {
        $redis = $this->getClient();

        for ($i = 0; $i < 10; $i++) {
            $redis->xadd('stream', ['key' . $i => 'val' . $i], $i . '-1');
        }

        $this->assertSame([], $redis->xrevrange('stream', '0-1', '1-1'));
        $this->assertSame(
            ['0-1' => ['key0' => 'val0']],
            $redis->xrevrange('stream', '0-1', '0-1')
        );
        $this->assertSame(
            ['1-1' => ['key1' => 'val1'], '0-1' => ['key0' => 'val0']],
            $redis->xrevrange('stream', '1-1', '0-1')
        );
        $this->assertSame(
            ['1-1' => ['key1' => 'val1'], '0-1' => ['key0' => 'val0']],
            $redis->xrevrange('stream', '1-1', '-')
        );
        $this->assertSame(
            ['9-1' => ['key9' => 'val9'], '8-1' => ['key8' => 'val8']],
            $redis->xrevrange('stream', '+', '8-1')
        );
        $this->assertSame(
            ['6-1' => ['key6' => 'val6'], '5-1' => ['key5' => 'val5']],
            $redis->xrevrange('stream', '6-1', '5-1')
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testMultipleKeys(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key1' => 'val1', 'key2' => 'val2'], '0-1');
        $redis->xadd('stream', ['key1' => 'val1', 'key2' => 'val2'], '1-1');

        $this->assertSame(
            [
                '1-1' => ['key1' => 'val1', 'key2' => 'val2'],
                '0-1' => ['key1' => 'val1', 'key2' => 'val2'],
            ],
            $redis->xrevrange('stream', '+', '-')
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->xrevrange('foo', '1-1', '0-1');
    }
}
