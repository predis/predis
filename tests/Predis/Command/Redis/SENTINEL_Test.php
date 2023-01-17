<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-server
 */
class SENTINEL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SENTINEL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SENTINEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['get-master-addr-by-name', 'predis:master'];
        $expected = ['get-master-addr-by-name', 'predis:master'];

        $command = $this->getCommandWithArgumentsArray($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $expected = ['127.0.0.1', '6379'];
        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($expected));
    }

    /**
     * @group disconnected
     */
    public function testSentinelMastersResponse(): void
    {
        $response = [
            [
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
            ],
        ];

        $expected = [
            [
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
            ],
        ];

        $command = $this->getCommandWithArguments('masters');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSentinelSlavesResponse(): void
    {
        $response = [
            [
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
            ],
        ];

        $expected = [
            [
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
            ],
        ];

        $command = $this->getCommandWithArguments('slaves', 'predis:master');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSentinelIsMasterDownByAddr(): void
    {
        $response = ['0', '7388832d5fdee6a2e301d6bbc5052bd1526d741c'];
        $expected = ['0', '7388832d5fdee6a2e301d6bbc5052bd1526d741c'];

        $command = $this->getCommandWithArguments('is-master-down-by-addr', '127.0.0.1', '6379');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSentinelGetMasterAddrByName(): void
    {
        $response = ['127.0.0.1', '6379'];
        $expected = ['127.0.0.1', '6379'];

        $command = $this->getCommandWithArguments('get-master-addr-by-name', 'predis:master');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testSentinelReset(): void
    {
        $response = 1;
        $expected = 1;

        $command = $this->getCommandWithArguments('reset', 'predis:*');

        $this->assertSame($expected, $command->parseResponse($response));
    }
}
