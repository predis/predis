<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster;

use LogicException;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use PredisTestCase;

class ReadConnectionSelectorTest extends PredisTestCase
{
    /**
     * @var CommandInterface
     */
    private $readCommand;
    private $writeCommand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->readCommand = new RawCommand('GET', ['key']);
        $this->writeCommand = new RawCommand('SET', ['key']);
    }

    /**
     * @group disconnected
     */
    public function testEmptyReadConnectionIdByDefault(): void
    {
        $selector = new ReadConnectionSelector();

        $masterConnectionId = '127.0.0.1:6379';

        $this->assertNull($selector->get($this->readCommand, $masterConnectionId));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionId(): void
    {
        $selector = new ReadConnectionSelector();

        $masterConnectionId = '127.0.0.1:6380';
        $replicaConnectionId = '127.0.0.1:6381';

        $selector->add($replicaConnectionId, $masterConnectionId);

        $this->assertSame($replicaConnectionId, $selector->get($this->readCommand, $masterConnectionId));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionIdForWriteCommand(): void
    {
        $selector = new ReadConnectionSelector();

        $masterConnectionId = '127.0.0.1:6380';
        $replicaConnectionId = '127.0.0.1:6381';

        $selector->add($replicaConnectionId, $masterConnectionId);

        $this->assertNull($selector->get($this->writeCommand, $masterConnectionId));
    }

    /**
     * @group disconnected
     */
    public function testGetARandomConnectionId(): void
    {
        $selector = new ReadConnectionSelector();

        $masterConnectionId = '127.0.0.1:6380';
        $replicaConnectionIds = [
            '127.0.0.1:6381',
            '127.0.0.1:6382',
            '127.0.0.1:6383',
        ];

        foreach ($replicaConnectionIds as $replicaConnectionId) {
            $selector->add($replicaConnectionId, $masterConnectionId);
        }

        $this->assertOneOf($replicaConnectionIds, $selector->get($this->readCommand, $masterConnectionId));
    }

    /**
     * @group disconnected
     */
    public function testGetARandomConnectionIdInReplicasMode(): void
    {
        $selector = new ReadConnectionSelector(ReadConnectionSelector::MODE_REPLICAS);

        $masterConnectionId = '127.0.0.1:6380';
        $replicaConnectionIds = [
            '127.0.0.1:6381',
            '127.0.0.1:6382',
            '127.0.0.1:6383',
        ];

        foreach ($replicaConnectionIds as $replicaConnectionId) {
            $selector->add($replicaConnectionId, $masterConnectionId);
        }

        $this->assertOneOf($replicaConnectionIds, $selector->get($this->readCommand, $masterConnectionId));
    }

    /**
     * @group disconnected
     */
    public function testGetARandomConnectionIdInRandomMode(): void
    {
        $selector = new ReadConnectionSelector(ReadConnectionSelector::MODE_REPLICAS);

        $masterConnectionId = '127.0.0.1:6380';
        $replicaConnectionIds = [
            '127.0.0.1:6381',
            '127.0.0.1:6382',
            '127.0.0.1:6383',
            $masterConnectionId,
        ];

        foreach ($replicaConnectionIds as $replicaConnectionId) {
            $selector->add($replicaConnectionId, $masterConnectionId);
        }

        $this->assertOneOf($replicaConnectionIds, $selector->get($this->readCommand, $masterConnectionId));
    }

    /**
     * @group disconnected
     */
    public function testGetAMasterConnectionIdInRandomMode(): void
    {
        $masterConnectionId = '127.0.0.1:6380';
        $replicaConnectionIds = [
            '127.0.0.1:6381',
            '127.0.0.1:6382',
            '127.0.0.1:6383',
        ];

        $selectorMock = $this->getMockBuilder(ReadConnectionSelector::class)
            ->setConstructorArgs([ReadConnectionSelector::MODE_RANDOM])
            ->onlyMethods(['getRandomConnection'])
            ->getMock();

        $selectorMock
            ->expects($this->once())
            ->method('getRandomConnection')
            ->willReturnCallback(function ($connections) use ($masterConnectionId) {
                if (!in_array($masterConnectionId, $connections, true)) {
                    throw new LogicException('Master connection ID MUST be in a connection list');
                }

                return $masterConnectionId;
            });

        foreach ($replicaConnectionIds as $replicaConnectionId) {
            $selectorMock->add($replicaConnectionId, $masterConnectionId);
        }

        $this->assertSame($masterConnectionId, $selectorMock->get($this->readCommand, $masterConnectionId));
    }
}
