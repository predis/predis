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
 * @group realm-transaction
 */
class TransactionMultiTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\TransactionMulti';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'MULTI';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $command = $this->getCommand();
        $command->setArguments(array());

        $this->assertSame(array(), $command->getArguments());
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
    public function testInitializesNewTransaction()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->multi());
        $this->assertEquals('QUEUED', $redis->echo('tx1'));
        $this->assertEquals('QUEUED', $redis->echo('tx2'));
    }

    /**
     * @group connected
     */
    public function testActuallyReturnsResponseObjectAbstraction()
    {
        $redis = $this->getClient();

        $this->assertInstanceOf('Predis\Response\Status', $redis->multi());
        $this->assertInstanceOf('Predis\Response\Status', $redis->echo('tx1'));
        $this->assertInstanceOf('Predis\Response\Status', $redis->echo('tx2'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR MULTI calls can not be nested
     */
    public function testThrowsExceptionWhenCallingMultiInsideTransaction()
    {
        $redis = $this->getClient();

        $redis->multi();
        $redis->multi();
    }
}
