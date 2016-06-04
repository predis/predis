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
 * @group realm-key
 */
class KeyMigrateTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\Redis\KeyMigrate';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'MIGRATE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('127.0.0.1', '6379', 'key', '0', '10');
        $expected = array('127.0.0.1', '6379', 'key', '0', '10');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsRedis300()
    {
        $arguments = array('127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE');
        $expected = array('127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithOptionsArray()
    {
        $arguments = array('127.0.0.1', '6379', 'key', '0', '10', array('COPY' => true, 'REPLACE' => true));
        $expected = array('127.0.0.1', '6379', 'key', '0', '10', 'COPY', 'REPLACE');

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

        $this->assertSame('OK', $command->parseResponse('OK'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testReturnsStatusNOKEYOnNonExistingKey()
    {
        $redis = $this->getClient();

        $this->assertEquals('NOKEY', $response = $redis->migrate('169.254.10.10', 16379, 'foo', 15, 1));
        $this->assertInstanceOf('Predis\Response\Status', $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     * @group slow
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage IOERR
     */
    public function testReturnsErrorOnUnreacheableDestination()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->migrate('169.254.10.10', 16379, 'foo', 15, 1);
    }
}
