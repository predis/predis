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
 * @group realm-server
 */
class ServerWaitTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ServerWait';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'WAIT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $command = $this->getCommand();

        $arguments = array(1);
        $expected = array(1, 0);

        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());

        $arguments = array(2, 200);
        $expected = array(2, 200);

        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());

        $arguments = array(array(2));
        $expected = array(2, 0);

        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());

        $arguments = array(array(2, 200));
        $expected = array(2, 200);

        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $expected = 1;
        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($expected));
    }

    /**
     * @group connected
     */
    public function testReturnsNumberOfSlavesWithSingleInstance()
    {
        $redis = $this->getClient();
        $redis->set('test.wait.key', 'token', 'ex', 5, 'nx');

        $this->assertInternalType('integer', $numSlaves = $redis->wait(1, 500));
        $this->assertEquals(0, $numSlaves);
    }

    /**
     * @group connected
     * @group redis-cluster
     */
    public function testReturnsNumberOfSlavesWithCluster()
    {
        $redis = $this->getClient(false, true);
        $redis->set('test.wait.key', 'token', 'ex', 5, 'nx');

        $this->assertInternalType('integer', $numSlaves = $redis->wait(REDIS_CLUSTER_NUM_SLAVES));
        $this->assertEquals(REDIS_CLUSTER_NUM_SLAVES, $numSlaves);
    }
}
