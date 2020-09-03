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

use Predis\Response\Status;

/**
 * @group commands
 * @group realm-server
 */
class COMMAND_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\COMMAND';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'COMMAND';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
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
    public function testParseResponse(): void
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
    public function testParseEmptyResponse(): void
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
    public function testReturnsEmptyCommandInfoOnNonExistingCommand(): void
    {
        $redis = $this->getClient();

        $this->assertCount(1, $response = $redis->command('INFO', 'FOOBAR'));
        $this->assertSame(array(null), $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.13
     */
    public function testReturnsCommandInfoOnExistingCommand(): void
    {
        $redis = $this->getClient();

        $expected = array(array('get', 2, array('readonly', 'fast'), 1, 1, 1));

        // NOTE: starting with Redis 6.0 and the introduction of Access Control
        // Lists, COMMAND INFO returns an additional array for each specified
        // command in yhe request with a list of the ACL categories associated
        // to a command. We simply append this additional array in the expected
        // response if the test suite is executed against Redis >= 6.0.
        if ($this->isRedisServerVersion('>=', '6.0')) {
            $expected[0][] = array('@read', '@string', '@fast');
        }

        $this->assertCount(1, $response = $redis->command('INFO', 'GET'));

        // NOTE: we use assertEquals instead of assertSame because Redis returns
        // flags as +STATUS responses, represented by Predis with instances of
        // Predis\Response\Status instead of plain strings. This class responds
        // to __toString() so the string conversion is implicit, but assertSame
        // checks for strict equality while assertEquals is loose.
        $this->assertEquals($expected, $response);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.13
     */
    public function testReturnsListOfCommandInfoWithNoArguments(): void
    {
        $redis = $this->getClient();

        $this->assertGreaterThan(100, count($response = $redis->command()));
    }
}
