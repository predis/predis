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
class MOVE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MOVE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MOVE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 10);
        $expected = array('key', 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group connected
     *
     * @todo Should be improved, this test fails when REDIS_SERVER_DBNUM is 0.
     */
    public function testMovesKeysToDifferentDatabases(): void
    {
        $db = REDIS_SERVER_DBNUM - 1;
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(1, $redis->move('foo', $db));
        $this->assertSame(0, $redis->exists('foo'));

        $redis->select($db);
        $this->assertSame(1, $redis->exists('foo'));

        $redis->del('foo');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnInvalidDatabases(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/ERR.*out of range/');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $redis->move('foo', 100000000);
    }
}
