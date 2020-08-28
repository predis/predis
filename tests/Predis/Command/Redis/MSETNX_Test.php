<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-string
 */
class MSETNX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MSETNX';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MSETNX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('foo', 'bar', 'hoge', 'piyo');
        $expected = array('foo', 'bar', 'hoge', 'piyo');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleNamedArray(): void
    {
        $arguments = array(array('foo' => 'bar', 'hoge' => 'piyo'));
        $expected = array('foo', 'bar', 'hoge', 'piyo');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(0, $this->getCommand()->parseResponse(0));
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     */
    public function testCreatesMultipleKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(1, $redis->msetnx('foo', 'bar', 'hoge', 'piyo'));
        $this->assertSame('bar', $redis->get('foo'));
        $this->assertSame('piyo', $redis->get('hoge'));
    }

    /**
     * @group connected
     */
    public function testCreatesMultipleKeysAndPreservesExistingOnes(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(0, $redis->msetnx('foo', 'barbar', 'hoge', 'piyo'));
        $this->assertSame('bar', $redis->get('foo'));
        $this->assertSame(0, $redis->exists('hoge'));
    }
}
