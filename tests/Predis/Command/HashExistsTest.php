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
class HashExistsTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\HashExists';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'HEXISTS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 'field');
        $expected = array('key', 'field');

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
    public function testReturnsExistenceOfSpecifiedField()
    {
        $redis = $this->getClient();

        $redis->hmset('metavars', 'foo', 'bar', 'hoge', 'piyo');

        $this->assertSame(1, $redis->hexists('metavars', 'foo'));
        $this->assertSame(0, $redis->hexists('metavars', 'lol'));
        $this->assertSame(0, $redis->hexists('unknown', 'foo'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongType()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->hexists('foo', 'bar');
    }
}
