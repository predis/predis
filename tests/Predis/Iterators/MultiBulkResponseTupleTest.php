<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Iterators;

use \PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\Client;

/**
 * @group realm-iterators
 */
class MultiBulkResponseTupleTest extends StandardTestCase
{
    /**
     * @group connected
     */
    public function testIterableMultibulk()
    {
        $client = $this->getClient();
        $client->zadd('metavars', 1, 'foo', 2, 'hoge', 3, 'lol');

        $this->assertInstanceOf('OuterIterator', $iterator = $client->zrange('metavars', 0, -1, 'withscores'));
        $this->assertInstanceOf('Predis\Iterators\MultiBulkResponseTuple', $iterator);
        $this->assertInstanceOf('Predis\Iterators\MultiBulkResponseSimple', $iterator->getInnerIterator());
        $this->assertTrue($iterator->valid());
        $this->assertSame(3, $iterator->count());

        $this->assertSame(array('foo', '1'), $iterator->current());
        $this->assertSame(1, $iterator->next());
        $this->assertTrue($iterator->valid());

        $this->assertSame(array('hoge', '2'), $iterator->current());
        $this->assertSame(2, $iterator->next());
        $this->assertTrue($iterator->valid());

        $this->assertSame(array('lol', '3'), $iterator->current());
        $this->assertSame(3, $iterator->next());
        $this->assertFalse($iterator->valid());

        $this->assertTrue($client->ping());
    }

    /**
     * @group connected
     */
    public function testGarbageCollectorDropsUnderlyingConnection()
    {
        $client = $this->getClient();
        $client->zadd('metavars', 1, 'foo', 2, 'hoge', 3, 'lol');

        $iterator = $client->zrange('metavars', 0, -1, 'withscores');

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
        $parameters = array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'iterable_multibulk' => true,
            'read_write_timeout' => 2,
        );

        $client = new Client($parameters, REDIS_SERVER_VERSION);
        $client->connect();
        $client->select(REDIS_SERVER_DBNUM);
        $client->flushdb();

        return $client;
    }

}
