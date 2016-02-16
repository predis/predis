<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

/**
 *
 */
class CompositeStreamConnectionTest extends PredisConnectionTestCase
{
    const CONNECTION_CLASS = 'Predis\Connection\CompositeStreamConnection';

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testReadsMultibulkResponsesAsIterators()
    {
        $connection = $this->createConnection(true);
        $profile = $this->getCurrentProfile();

        $connection->getProtocol()->useIterableMultibulk(true);

        $connection->executeCommand($profile->createCommand('rpush', array('metavars', 'foo', 'hoge', 'lol')));
        $connection->writeRequest($profile->createCommand('lrange', array('metavars', 0, -1)));

        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulkIterator', $iterator = $connection->read());
        $this->assertSame(array('foo', 'hoge', 'lol'), iterator_to_array($iterator));
    }
}
