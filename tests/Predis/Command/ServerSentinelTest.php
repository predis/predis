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
class ServerSentinelTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ServerSentinel';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'SENTINEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('get-master-addr-by-name', 'predis:master');
        $expected = array('get-master-addr-by-name', 'predis:master');

        $command = $this->getCommandWithArgumentsArray($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $expected = array('127.0.0.1', '6379');
        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($expected));
    }

    /**
     * @group disconnected
     */
    public function testSentinelMastersResponse()
    {
        $response = array(
            array(
                'name', 'predis:master',
                'ip', '127.0.0.1',
                'port', '6379',
                'runid', '89f6128a7e5780aa6ef7d4d7022cfafbf799b3ab',
                'flags', 'master',
                'pending-commands', '0',
                'last-ok-ping-reply', '386',
                'last-ping-reply', '386',
                'info-refresh', '9926',
                'num-slaves', '1',
                'num-other-sentinels', '0',
                'quorum', '2',
            ),
        );

        $expected = array(
            array(
                'name' => 'predis:master',
                'ip' => '127.0.0.1',
                'port' => '6379',
                'runid' => '89f6128a7e5780aa6ef7d4d7022cfafbf799b3ab',
                'flags' => 'master',
                'pending-commands' => '0',
                'last-ok-ping-reply' => '386',
                'last-ping-reply' => '386',
                'info-refresh' => '9926',
                'num-slaves' => '1',
                'num-other-sentinels' => '0',
                'quorum' => '2',
            ),
        );

        $command = $this->getCommandWithArguments('masters');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSentinelSlavesResponse()
    {
        $response = array(
            array(
                'name', '127.0.0.1:6380',
                'ip', '127.0.0.1',
                'port', '6380',
                'runid', '92aea60e4fead2507cccd6574e4c7139d401d0ae',
                'flags', 'slave',
                'pending-commands', '0',
                'last-ok-ping-reply', '1011',
                'last-ping-reply', '1011',
                'info-refresh', '4366',
                'master-link-down-time', '0',
                'master-link-status', 'ok',
                'master-host', '127.0.0.1',
                'master-port', '6379',
                'slave-priority', '100',
            ),
        );

        $expected = array(
            array(
                'name' => '127.0.0.1:6380',
                'ip' => '127.0.0.1',
                'port' => '6380',
                'runid' => '92aea60e4fead2507cccd6574e4c7139d401d0ae',
                'flags' => 'slave',
                'pending-commands' => '0',
                'last-ok-ping-reply' => '1011',
                'last-ping-reply' => '1011',
                'info-refresh' => '4366',
                'master-link-down-time' => '0',
                'master-link-status' => 'ok',
                'master-host' => '127.0.0.1',
                'master-port' => '6379',
                'slave-priority' => '100',
            ),
        );

        $command = $this->getCommandWithArguments('slaves', 'predis:master');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSentinelIsMasterDownByAddr()
    {
        $response = array('0', '7388832d5fdee6a2e301d6bbc5052bd1526d741c');
        $expected = array('0', '7388832d5fdee6a2e301d6bbc5052bd1526d741c');

        $command = $this->getCommandWithArguments('is-master-down-by-addr', '127.0.0.1', '6379');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSentinelGetMasterAddrByName()
    {
        $response = array('127.0.0.1', '6379');
        $expected = array('127.0.0.1', '6379');

        $command = $this->getCommandWithArguments('get-master-addr-by-name', 'predis:master');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSentinelReset()
    {
        $response = 1;
        $expected = 1;

        $command = $this->getCommandWithArguments('reset', 'predis:*');

        $this->assertSame($expected, $command->parseResponse($response));
    }
}
