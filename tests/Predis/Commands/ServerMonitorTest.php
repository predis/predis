<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 * @group commands
 * @group realm-server
 * @group realm-monitor
 */
class ServerMonitorTest extends CommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Commands\ServerMonitor';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'MONITOR';
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
        $this->assertTrue($this->getCommand()->parseResponse(true));
    }

    /**
     * @group connected
     */
    public function testReturnsTrueAndReadsEventsFromTheConnection()
    {
        $connection = $this->getClient()->getConnection();
        $command = $this->getCommand();

        $this->assertTrue($connection->executeCommand($command));
        $this->assertRegExp('/\d+.\d+(\s?\(db \d+\))? "MONITOR"/', $connection->read());
    }
}
