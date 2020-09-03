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
class MGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MGET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MGET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key1', 'key2', 'key3');
        $expected = array('key1', 'key2', 'key3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = array(array('key1', 'key2', 'key3'));
        $expected = array('key1', 'key2', 'key3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('value1', 'value2', 'value3');
        $expected = array('value1', 'value2', 'value3');

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     */
    public function testReturnsArrayOfValues(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->set('hoge', 'piyo');

        $this->assertSame(array('bar', 'piyo'), $redis->mget('foo', 'hoge'));
    }

    /**
     * @group connected
     */
    public function testReturnsArrayWithNullValuesOnNonExistingKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array(null, null), $redis->mget('foo', 'hoge'));
    }

    /**
     * @group connected
     */
    public function testDoesNotThrowExceptionOnWrongType(): void
    {
        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');
        $this->assertSame(array(null), $redis->mget('metavars'));
    }
}
