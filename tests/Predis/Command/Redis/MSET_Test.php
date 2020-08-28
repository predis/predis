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
class MSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MSET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MSET';
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
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     */
    public function testCreatesMultipleKeys(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->mset('foo', 'bar', 'hoge', 'piyo'));
        $this->assertSame('bar', $redis->get('foo'));
        $this->assertSame('piyo', $redis->get('hoge'));
    }
}
