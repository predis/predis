<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @group commands
 * @group realm-hash
 */
class HashSetTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\HashSet';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'HSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 'field', 'value');
        $expected = array('key', 'field', 'value');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group connected
     */
    public function testSetsValueOfSpecifiedField()
    {
        $redis = $this->getClient();

        $this->assertSame(1, $redis->hset('metavars', 'foo', 'bar'));
        $this->assertSame(1, $redis->hset('metavars', 'hoge', 'piyo'));

        $this->assertSame(array('bar', 'piyo'), $redis->hmget('metavars', 'foo', 'hoge'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongType()
    {
        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->hset('metavars', 'foo', 'bar');
    }
}
