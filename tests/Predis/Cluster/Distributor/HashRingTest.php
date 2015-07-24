<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster\Distributor;

/**
 *
 */
class HashRingTest extends PredisDistributorTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getDistributorInstance()
    {
        return new HashRing();
    }

    /**
     * @group disconnected
     */
    public function testHash()
    {
        $ring = $this->getDistributorInstance();

        $this->assertEquals(crc32('foobar'), $ring->hash('foobar'));
    }

    /**
     * @group disconnected
     */
    public function testSingleNodeInRing()
    {
        $node = '127.0.0.1:7000';

        $ring = $this->getDistributorInstance();
        $ring->add($node);

        $expected = array_fill(0, 20, $node);
        $actual = $this->getNodes($ring, 20);

        $this->assertSame($expected, $actual);
    }

    /**
     * @group disconnected
     */
    public function testMultipleNodesInRing()
    {
        $ring = $this->getSampleDistribution(array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        ));

        $expected = array(
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
            '127.0.0.1:7002',
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
            '127.0.0.1:7002',
            '127.0.0.1:7002',
            '127.0.0.1:7002',
            '127.0.0.1:7000',
            '127.0.0.1:7002',
            '127.0.0.1:7002',
            '127.0.0.1:7002',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        );

        $actual = $this->getNodes($ring, 20);

        $this->assertSame($expected, $actual);
    }

    /**
     * @group disconnected
     */
    public function testSubsequendAddAndRemoveFromRing()
    {
        $ring = $this->getDistributorInstance();

        $expected1 = array_fill(0, 10, '127.0.0.1:7000');
        $expected3 = array_fill(0, 10, '127.0.0.1:7001');
        $expected2 = array(
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
        );

        $ring->add('127.0.0.1:7000');
        $actual1 = $this->getNodes($ring, 10);

        $ring->add('127.0.0.1:7001');
        $actual2 = $this->getNodes($ring, 10);

        $ring->remove('127.0.0.1:7000');
        $actual3 = $this->getNodes($ring, 10);

        $this->assertSame($expected1, $actual1);
        $this->assertSame($expected2, $actual2);
        $this->assertSame($expected3, $actual3);
    }

    /**
     * @group disconnected
     */
    public function testGetByValue()
    {
        $ring = $this->getSampleDistribution(array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        ));

        $this->assertSame('127.0.0.1:7001', $ring->get('uid:256'));
        $this->assertSame('127.0.0.1:7001', $ring->get('uid:281'));
        $this->assertSame('127.0.0.1:7000', $ring->get('uid:312'));
        $this->assertSame('127.0.0.1:7001', $ring->get('uid:432'));
        $this->assertSame('127.0.0.1:7002', $ring->get('uid:500'));
        $this->assertSame('127.0.0.1:7000', $ring->get('uid:641'));
    }

    /**
     * @group disconnected
     */
    public function testGetByHash()
    {
        $ring = $this->getSampleDistribution(array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        ));

        $this->assertSame('127.0.0.1:7001', $ring->getByHash(PHP_INT_SIZE == 4 ? -1249390087 : 3045577209)); // uid:256
        $this->assertSame('127.0.0.1:7001', $ring->getByHash(PHP_INT_SIZE == 4 ? -1639106025 : 2655861271)); // uid:281
        $this->assertSame('127.0.0.1:7000', $ring->getByHash(PHP_INT_SIZE == 4 ?  -683361581 : 3611605715)); // uid:312
        $this->assertSame('127.0.0.1:7001', $ring->getByHash(PHP_INT_SIZE == 4 ?  -532820268 : 3762147028)); // uid:432
        $this->assertSame('127.0.0.1:7002', $ring->getByHash(PHP_INT_SIZE == 4 ?   618436108 :  618436108)); // uid:500
        $this->assertSame('127.0.0.1:7000', $ring->getByHash(PHP_INT_SIZE == 4 ?   905043399 :  905043399)); // uid:641
    }

    /**
     * @group disconnected
     */
    public function testGetBySlot()
    {
        $ring = $this->getSampleDistribution(array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        ));

        $this->assertSame('127.0.0.1:7001', $ring->getBySlot(PHP_INT_SIZE == 4 ? -1255075679 : 3039891617)); // uid:256
        $this->assertSame('127.0.0.1:7001', $ring->getBySlot(PHP_INT_SIZE == 4 ? -1642314910 : 2652652386)); // uid:281
        $this->assertSame('127.0.0.1:7000', $ring->getBySlot(PHP_INT_SIZE == 4 ?  -687739295 : 3607228001)); // uid:312
        $this->assertSame('127.0.0.1:7001', $ring->getBySlot(PHP_INT_SIZE == 4 ?  -544842345 : 3750124951)); // uid:432
        $this->assertSame('127.0.0.1:7002', $ring->getBySlot(PHP_INT_SIZE == 4 ?   609245004 :  609245004)); // uid:500
        $this->assertSame('127.0.0.1:7000', $ring->getBySlot(PHP_INT_SIZE == 4 ?   902549909 :  902549909)); // uid:641

        // Test first and last slots
        $this->assertSame('127.0.0.1:7001', $ring->getBySlot(PHP_INT_SIZE == 4 ? -2096102881 : 2198864415));
        $this->assertSame('127.0.0.1:7002', $ring->getBySlot(PHP_INT_SIZE == 4 ?  2146453549 : 2146453549));

        // Test non-existing slot
        $this->assertNull($ring->getBySlot(0));
    }

    /**
     * @group disconnected
     */
    public function testCallbackToGetNodeHash()
    {
        $node = '127.0.0.1:7000';
        $callable = $this->getMock('stdClass', array('__invoke'));

        $callable->expects($this->once())
                 ->method('__invoke')
                 ->with($node)
                 ->will($this->returnValue($node));

        $distributor = new HashRing(HashRing::DEFAULT_REPLICAS, $callable);
        $distributor->add($node);

        $this->getNodes($distributor);
    }
}
