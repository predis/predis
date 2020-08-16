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

use Predis\Response\Status;

/**
 * @group commands
 * @group realm-server
 */
class ServerCommandTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ServerCommand';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'COMMAND';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('INFO', 'DEL');
        $expected = array('INFO', 'DEL');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $raw = array(
            array('get', 2, array(new Status('readonly'), new Status('fast')), 1, 1, 1),
            array('set', -3, array(new Status('write'), new Status('denyoom')), 1, 1, 1),
            array('watch', -2, array(new Status('readonly'), new Status('noscript'), new Status('fast')), 1, -1, 1),
            array('unwatch', 1, array(new Status('readonly'), new Status('noscript'), new Status('fast')), 0, 0, 0),
            array('info', -1, array(new Status('readonly'), new Status('loading'), new Status('stale')), 0, 0, 0),
        );

        $expected = $raw;

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseEmptyResponse()
    {
        $raw = array(null);
        $expected = array(null);

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.13
     */
    public function testReturnsEmptyCommandInfoOnNonExistingCommand()
    {
        $redis = $this->getClient();

        $this->assertCount(1, $response = $redis->command('INFO', 'FOOBAR'));
        $this->assertSame(array(null), $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.13
     */
    public function testReturnsCommandInfoOnExistingCommand()
    {
        $redis = $this->getClient();

        // NOTE: we use assertEquals instead of assertSame because Redis returns
        // flags as +STATUS responses, represented by Predis with instances of
        // Predis\Response\Status instead of plain strings. This class responds
        // to __toString() so the string conversion is implicit, but assertSame
        // checks for strict equality while assertEquals is loose.
        $expected = array(array('get', 2, array('readonly', 'fast'), 1, 1, 1));
        $this->assertCount(1, $response = $redis->command('INFO', 'GET'));
        $this->assertEquals($expected, $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.13
     */
    public function testReturnsListOfCommandInfoWithNoArguments()
    {
        $redis = $this->getClient();

        $this->assertGreaterThan(100, count($response = $redis->command()));
    }
}
