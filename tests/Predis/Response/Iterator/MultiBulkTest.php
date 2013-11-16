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

use \PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\Client;
use Predis\Connection\ComposableStreamConnection;
use Predis\Connection\ConnectionParameters;
use Predis\Protocol\Text\ProtocolProcessor as TextProtocolProcessor;

/**
 * @group realm-iterators
 */
class MultiBulkTest extends StandardTestCase
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
        $this->assertTrue($iterator->valid());
        $this->assertSame(3, $iterator->count());

        $this->assertSame('foo', $iterator->current());
        $this->assertSame(1, $iterator->next());
        $this->assertTrue($iterator->valid());

        $this->assertSame('hoge', $iterator->current());
        $this->assertSame(2, $iterator->next());
        $this->assertTrue($iterator->valid());

        $this->assertSame('lol', $iterator->current());
        $this->assertSame(3, $iterator->next());
        $this->assertFalse($iterator->valid());

        $this->assertTrue($client->ping());
    }

    /**
     * @group connected
     */
    public function testIterableMultibulkCanBeWrappedAsTupleIterator()
    {
        $client = $this->getClient();
        $client->mset('foo', 'bar', 'hoge', 'piyo');

        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulk', $iterator = $client->mget('foo', 'bar'));
        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulkTuple', $iterator->asTuple());
    }

    /**
     * @group connected
     */
    public function testDropWithFalseConsumesReplyFromUnderlyingConnection()
    {
        $client = $this->getClient();
        $client->rpush('metavars', 'foo', 'hoge', 'lol');

        $iterator = $client->lrange('metavars', 0, -1);
        $iterator->drop(false);

        $this->assertTrue($client->isConnected());
        $this->assertTrue($client->ping());
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
        $this->assertTrue($client->ping());
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
        $this->assertTrue($client->ping());
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
        $parameters = new ConnectionParameters(array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'read_write_timeout' => 2,
        ));

        $options = array(
            'profile' => REDIS_SERVER_VERSION,
        );

        $protocol = new TextProtocolProcessor();
        $protocol->useIterableMultibulk(true);

        $connection = new ComposableStreamConnection($parameters, $protocol);

        $client = new Client($connection, $options);
        $client->connect();
        $client->select(REDIS_SERVER_DBNUM);
        $client->flushdb();

        return $client;
    }

}
