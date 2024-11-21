<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Response\Iterator;

use Predis\Client;
use Predis\ClientInterface;
use Predis\Connection\CompositeStreamConnection;
use Predis\Connection\NodeConnectionInterface;
use Predis\Protocol\Text\ProtocolProcessor as TextProtocolProcessor;
use PredisTestCase;

/**
 * @group realm-iterators
 */
class MultiBulkTupleTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testInitiatedMultiBulkIteratorsAreNotValid(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Cannot initialize a tuple iterator using an already initiated iterator');

        /** @var NodeConnectionInterface */
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $iterator = new MultiBulk($connection, 2);
        $iterator->next();

        new MultiBulkTuple($iterator);
    }

    /**
     * @group disconnected
     */
    public function testMultiBulkWithOddSizesAreInvalid(): void
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Invalid response size for a tuple iterator');

        /** @var NodeConnectionInterface */
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $iterator = new MultiBulk($connection, 3);

        new MultiBulkTuple($iterator);
    }

    /**
     * @group connected
     */
    public function testIterableMultibulk(): void
    {
        $client = $this->getClient();
        $client->zadd('metavars', 1, 'foo', 2, 'hoge', 3, 'lol');

        /** @var MultiBulkIterator */
        $multibulk = $client->zrange('metavars', '0', '-1', 'withscores');
        $iterator = new MultiBulkTuple($multibulk);

        $this->assertInstanceOf('OuterIterator', $iterator);
        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulkTuple', $iterator);
        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulk', $iterator->getInnerIterator());
        $this->assertTrue($iterator->valid());
        $this->assertSame(3, $iterator->count());

        $this->assertSame(['foo', '1'], $iterator->current());
        $iterator->next();
        $this->assertTrue($iterator->valid());

        $this->assertSame(['hoge', '2'], $iterator->current());
        $iterator->next();
        $this->assertTrue($iterator->valid());

        $this->assertSame(['lol', '3'], $iterator->current());
        $iterator->next();
        $this->assertFalse($iterator->valid());

        $this->assertEquals('PONG', $client->ping());
    }

    /**
     * @group connected
     */
    public function testGarbageCollectorDropsUnderlyingConnection(): void
    {
        $client = $this->getClient();
        $client->zadd('metavars', 1, 'foo', 2, 'hoge', 3, 'lol');

        /** @var MultiBulkIterator */
        $multibulk = $client->zrange('metavars', '0', '-1', 'withscores');
        $iterator = new MultiBulkTuple($multibulk);

        unset($iterator);

        $this->assertFalse($client->isConnected());
        $this->assertEquals('PONG', $client->ping());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a new client instance.
     *
     * @return ClientInterface
     */
    protected function getClient(): ClientInterface
    {
        $parameters = $this->getParameters(['read_write_timeout' => 2]);

        $protocol = new TextProtocolProcessor();
        $protocol->useIterableMultibulk(true);

        $connection = new CompositeStreamConnection($parameters, $protocol);

        $client = new Client($connection);
        $client->connect();
        $client->flushdb();

        return $client;
    }
}
