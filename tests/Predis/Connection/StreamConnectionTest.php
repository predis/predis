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
class StreamConnectionTest extends PredisConnectionTestCase
{
    const CONNECTION_CLASS = 'Predis\Connection\StreamConnection';

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testPersistentParameterWithFalseLikeValues()
    {
        if ($this->isHHVM()) {
            $this->markTestSkipped('This test does not currently work on HHVM.');
        }

        $connection1 = $this->createConnectionWithParams(array('persistent' => 0));
        $this->assertNonPersistentConnection($connection1);

        $connection2 = $this->createConnectionWithParams(array('persistent' => false));
        $this->assertNonPersistentConnection($connection2);

        $connection3 = $this->createConnectionWithParams(array('persistent' => '0'));
        $this->assertNonPersistentConnection($connection3);

        $connection4 = $this->createConnectionWithParams(array('persistent' => 'false'));
        $this->assertNonPersistentConnection($connection4);
    }

    /**
     * @group connected
     */
    public function testPersistentParameterWithTrueLikeValues()
    {
        if ($this->isHHVM()) {
            $this->markTestSkipped('This test does not currently work on HHVM.');
        }

        $connection1 = $this->createConnectionWithParams(array('persistent' => 1));
        $this->assertPersistentConnection($connection1);

        $connection2 = $this->createConnectionWithParams(array('persistent' => true));
        $this->assertPersistentConnection($connection2);

        $connection3 = $this->createConnectionWithParams(array('persistent' => '1'));
        $this->assertPersistentConnection($connection3);

        $connection4 = $this->createConnectionWithParams(array('persistent' => 'true'));
        $this->assertPersistentConnection($connection4);

        $connection1->disconnect();
    }

    /**
     * @group connected
     */
    public function testPersistentConnectionsToSameNodeShareResource()
    {
        if ($this->isHHVM()) {
            $this->markTestSkipped('This test does not currently work on HHVM.');
        }

        $connection1 = $this->createConnectionWithParams(array('persistent' => true));
        $connection2 = $this->createConnectionWithParams(array('persistent' => true));

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertSame($connection1->getResource(), $connection2->getResource());

        $connection1->disconnect();
    }

    /**
     * @group connected
     */
    public function testPersistentConnectionsToSameNodeDoNotShareResourceUsingDifferentPersistentID()
    {
        if ($this->isHHVM()) {
            $this->markTestSkipped('This test does not currently work on HHVM.');
        }

        $connection1 = $this->createConnectionWithParams(array('persistent' => 'conn1'));
        $connection2 = $this->createConnectionWithParams(array('persistent' => 'conn2'));

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertNotSame($connection1->getResource(), $connection2->getResource());
    }
}
