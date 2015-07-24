<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Response\Iterator;

use Predis\Client;
use Predis\Connection\CompositeStreamConnection;
use Predis\Protocol\Text\ProtocolProcessor as TextProtocolProcessor;
use PredisTestCase;

/**
 * @group realm-iterators
 */
class MultiBulkTupleTest extends PredisTestCase
{
    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot initialize a tuple iterator using an already initiated iterator.
     */
    public function testInitiatedMultiBulkIteratorsAreNotValid()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $iterator = new MultiBulk($connection, 2);
        $iterator->next();

        new MultiBulkTuple($iterator);
    }

    /**
     * @group disconnected
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Invalid response size for a tuple iterator.
     */
    public function testMultiBulkWithOddSizesAreInvalid()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $iterator = new MultiBulk($connection, 3);

        new MultiBulkTuple($iterator);
    }

    /**
     * @group connected
     */
    public function testIterableMultibulk()
    {
        $client = $this->getClient();
        $client->zadd('metavars', 1, 'foo', 2, 'hoge', 3, 'lol');

        $iterator = new MultiBulkTuple($client->zrange('metavars', '0', '-1', 'withscores'));

        $this->assertInstanceOf('OuterIterator', $iterator);
        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulkTuple', $iterator);
        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulk', $iterator->getInnerIterator());
        $this->assertTrue($iterator->valid());
        $this->assertSame(3, $iterator->count());

        $this->assertSame(array('foo', '1'), $iterator->current());
        $iterator->next();
        $this->assertTrue($iterator->valid());

        $this->assertSame(array('hoge', '2'), $iterator->current());
        $iterator->next();
        $this->assertTrue($iterator->valid());

        $this->assertSame(array('lol', '3'), $iterator->current());
        $iterator->next();
        $this->assertFalse($iterator->valid());

        $this->assertEquals('PONG', $client->ping());
    }

    /**
     * @group connected
     */
    public function testGarbageCollectorDropsUnderlyingConnection()
    {
        $client = $this->getClient();
        $client->zadd('metavars', 1, 'foo', 2, 'hoge', 3, 'lol');

        $iterator = new MultiBulkTuple($client->zrange('metavars', '0', '-1', 'withscores'));

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
     * @return Client
     */
    protected function getClient()
    {
        $parameters = $this->getParameters(array('read_write_timeout' => 2));

        $protocol = new TextProtocolProcessor();
        $protocol->useIterableMultibulk(true);

        $connection = new CompositeStreamConnection($parameters, $protocol);

        $client = new Client($connection);
        $client->connect();
        $client->flushdb();

        return $client;
    }
}
