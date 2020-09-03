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
 * In order to support the output of SLOWLOG, the backend connection must be
 * able to parse nested multibulk responses deeper than 2 levels.
 *
 * @group commands
 * @group realm-server
 */
class SLOWLOG_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SLOWLOG';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SLOWLOG';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('GET', '2');
        $expected = array('GET', '2');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * This is the response type for SLOWLOG GET.
     *
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array(array(0, 1323163469, 12451, array('SORT', 'list:unordered')));
        $expected = array(
            array(
                'id' => 0,
                'timestamp' => 1323163469,
                'duration' => 12451,
                'command' => array('SORT', 'list:unordered'),
            ),
        );

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * This is the response type for SLOWLOG LEN.
     *
     * @group disconnected
     */
    public function testParseResponseInteger(): void
    {
        $command = $this->getCommand();

        $this->assertSame(10, $command->parseResponse(10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.12
     */
    public function testReturnsAnArrayOfLoggedCommands(): void
    {
        $redis = $this->getClient();

        $config = $redis->config('get', 'slowlog-log-slower-than');
        $threshold = array_pop($config);

        $redis->config('set', 'slowlog-log-slower-than', 0);
        $redis->set('foo', 'bar');

        $this->assertIsArray($slowlog = $redis->slowlog('GET'));
        $this->assertGreaterThan(0, count($slowlog));

        $this->assertIsArray($slowlog[0]);
        $this->assertGreaterThan(0, $slowlog[0]['id']);
        $this->assertGreaterThan(0, $slowlog[0]['timestamp']);
        $this->assertGreaterThan(0, $slowlog[0]['duration']);
        $this->assertIsArray($slowlog[0]['command']);

        $redis->config('set', 'slowlog-log-slower-than', $threshold);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.12
     */
    public function testCanResetTheLog(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->slowlog('RESET'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.12
     */
    public function testThrowsExceptionOnInvalidSubcommand(): void
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();

        $redis->slowlog('INVALID');
    }
}
