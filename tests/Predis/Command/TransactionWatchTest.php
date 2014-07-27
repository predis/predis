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
class TransactionWatchTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\TransactionWatch';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'WATCH';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key1', 'key2', 'key3');
        $expected = array('key1', 'key2', 'key3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray()
    {
        $arguments = array(array('key1', 'key2', 'key3'));
        $expected = array('key1', 'key2', 'key3');

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
    public function testAbortsTransactionOnExternalWriteOperations()
    {
        $redis1 = $this->getClient();
        $redis2 = $this->getClient();

        $redis1->mset('foo', 'bar', 'hoge', 'piyo');

        $this->assertEquals('OK', $redis1->watch('foo', 'hoge'));
        $this->assertEquals('OK', $redis1->multi());
        $this->assertEquals('QUEUED', $redis1->get('foo'));
        $this->assertEquals('OK', $redis2->set('foo', 'hijacked'));
        $this->assertNull($redis1->exec());
        $this->assertSame('hijacked', $redis1->get('foo'));
    }

    /**
     * @group connected
     */
    public function testCanWatchNotYetExistingKeys()
    {
        $redis1 = $this->getClient();
        $redis2 = $this->getClient();

        $this->assertEquals('OK', $redis1->watch('foo'));
        $this->assertEquals('OK', $redis1->multi());
        $this->assertEquals('QUEUED', $redis1->set('foo', 'bar'));
        $this->assertEquals('OK', $redis2->set('foo', 'hijacked'));
        $this->assertNull($redis1->exec());
        $this->assertSame('hijacked', $redis1->get('foo'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR WATCH inside MULTI is not allowed
     */
    public function testThrowsExceptionWhenCallingInsideTransaction()
    {
        $redis = $this->getClient();

        $redis->multi();
        $redis->watch('foo');
    }
}
