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

namespace Predis\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Command\RawCommand;
use Predis\Response\Error as ErrorResponse;

class CompositeStreamConnectionTest extends PredisConnectionTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getConnectionClass(): string
    {
        return 'Predis\Connection\CompositeStreamConnection';
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInitializationCommandFailure(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');
        $this->expectExceptionMessage('Failed: ERR invalid DB index [tcp://127.0.0.1:6379]');

        $cmdSelect = RawCommand::create('SELECT', '1000');

        /** @var NodeConnectionInterface|MockObject */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['write', 'read', 'createResource'])
            ->setConstructorArgs([new Parameters()])
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('write');
        $connection
            ->method('read')
            ->willReturn(
                new ErrorResponse('ERR invalid DB index')
            );

        $connection->method('createResource');

        $connection->addConnectCommand($cmdSelect);
        $connection->connect();
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testReadsMultibulkResponsesAsIterators(): void
    {
        /** @var CompositeConnectionInterface */
        $connection = $this->createConnection(true);
        $commands = $this->getCommandFactory();

        $connection->getProtocol()->useIterableMultibulk(true);

        $connection->executeCommand($commands->create('rpush', ['metavars', 'foo', 'hoge', 'lol']));
        $connection->writeRequest($commands->create('lrange', ['metavars', 0, -1]));

        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulkIterator', $iterator = $connection->read());
        $this->assertSame(['foo', 'hoge', 'lol'], iterator_to_array($iterator));
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithFalseLikeValues(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 0]);
        $this->assertNonPersistentConnection($connection1->getResource()->detach());

        $connection2 = $this->createConnectionWithParams(['persistent' => false]);
        $this->assertNonPersistentConnection($connection2->getResource()->detach());

        $connection3 = $this->createConnectionWithParams(['persistent' => '0']);
        $this->assertNonPersistentConnection($connection3->getResource()->detach());

        $connection4 = $this->createConnectionWithParams(['persistent' => 'false']);
        $this->assertNonPersistentConnection($connection4->getResource()->detach());
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithTrueLikeValues(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 1]);
        $this->assertPersistentConnection($connection1->getResource()->detach());

        $connection2 = $this->createConnectionWithParams(['persistent' => true]);
        $this->assertPersistentConnection($connection2->getResource()->detach());

        $connection3 = $this->createConnectionWithParams(['persistent' => '1']);
        $this->assertPersistentConnection($connection3->getResource()->detach());

        $connection4 = $this->createConnectionWithParams(['persistent' => 'true']);
        $this->assertPersistentConnection($connection4->getResource()->detach());

        $connection1->disconnect();
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentConnectionsToSameNodeShareResource(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => true]);
        $connection2 = $this->createConnectionWithParams(['persistent' => true]);

        $this->assertPersistentConnection($connection1->getResource()->detach());
        $this->assertPersistentConnection($connection2->getResource()->detach());

        $this->assertEquals($connection1->getResource(), $connection2->getResource());

        $connection1->disconnect();
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentConnectionsToSameNodeDoNotShareResourceUsingDifferentPersistentID(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 'conn1']);
        $connection2 = $this->createConnectionWithParams(['persistent' => 'conn2']);

        $this->assertPersistentConnection($connection1->getResource()->detach());
        $this->assertPersistentConnection($connection2->getResource()->detach());

        $this->assertNotSame($connection1->getResource(), $connection2->getResource());
    }
}
