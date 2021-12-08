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
 * @group realm-connection
 */
class ConnectionSelectTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ConnectionSelect';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'SELECT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array(10);
        $expected = array(10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     */
    public function testCanSelectDifferentDatabase()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertEquals('OK', $redis->select(REDIS_SERVER_DBNUM - 1));
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessageRegExp /ERR.*DB index/
     */
    public function testThrowsExceptionOnUnexpectedDatabaseRange()
    {
        $redis = $this->getClient();

        $redis->select(100000000);
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessageRegExp /ERR (invalid DB index|value is not)/
     */
    public function testThrowsExceptionOnUnexpectedDatabaseName()
    {
        $redis = $this->getClient();

        $redis->select('x');
    }
}
