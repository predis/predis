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
class MultiBulkTest extends PredisTestCase
{
    /**
     * @group connected
     */
    public function testIterableMultibulk()
    {
        $client = $this->getClient();
        $client->rpush('metavars', 'foo', 'hoge', 'lol');

        $this->assertInstanceOf('Iterator', $iterator = $client->lrange('metavars', 0, -1));
        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulk', $iterator);
        $iterator->valid();
        $this->assertSame(3, $iterator->count());

        $this->assertSame('foo', $iterator->current());
        $iterator->next();
        $this->assertTrue($iterator->valid());

        $this->assertSame('hoge', $iterator->current());
        $iterator->next();
        $this->assertTrue($iterator->valid());

        $this->assertSame('lol', $iterator->current());
        $iterator->next();
        $this->assertFalse($iterator->valid());

        $this->assertEquals('PONG', $client->ping());
    }

    /**
     * @group connected
     */
    public function testDropWithFalseConsumesResponseFromUnderlyingConnection()
    {
        $client = $this->getClient();
        $client->rpush('metavars', 'foo', 'hoge', 'lol');

        $iterator = $client->lrange('metavars', 0, -1);
        $iterator->drop(false);

        $this->assertTrue($client->isConnected());
        $this->assertEquals('PONG', $client->ping());
    }

    /**
     * @group connected
     */
    public function testDropWithTrueDropsUnderlyingConnection()
    {
        $client = $this->getClient();
        $client->rpush('metavars', 'foo', 'hoge', 'lol');

        $iterator = $client->lrange('metavars', 0, -1);
        $iterator->drop(true);

        $this->assertFalse($client->isConnected());
        $this->assertEquals('PONG', $client->ping());
    }

    /**
     * @group connected
     */
    public function testGarbageCollectorDropsUnderlyingConnection()
    {
        $client = $this->getClient();
        $client->rpush('metavars', 'foo', 'hoge', 'lol');

        $iterator = $client->lrange('metavars', 0, -1);

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
