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
 * @group realm-transaction
 */
class EXEC_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\EXEC';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'EXEC';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(array());

        $this->assertSame(array(), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('tx1', 'tx2');
        $expected = array('tx1', 'tx2');

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     */
    public function testExecutesTransactionAndReturnsArrayOfResponses(): void
    {
        $redis = $this->getClient();

        $redis->multi();
        $redis->echo('tx1');
        $redis->echo('tx2');

        $this->assertSame(array('tx1', 'tx2'), $redis->exec());
    }

    /**
     * @group connected
     */
    public function testReturnsEmptyArrayOnEmptyTransactions(): void
    {
        $redis = $this->getClient();

        $redis->multi();

        $this->assertSame(array(), $redis->exec());
    }

    /**
     * @group connected
     */
    public function testResponsesOfTransactionsAreNotParsed(): void
    {
        $redis = $this->getClient();

        $redis->multi();
        $redis->ping();
        $redis->set('foo', 'bar');
        $redis->exists('foo');

        $this->assertEquals(array('PONG', 'OK', 1), $redis->exec());
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionWhenCallingOutsideTransaction(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR EXEC without MULTI');

        $redis = $this->getClient();

        $redis->exec();
    }
}
