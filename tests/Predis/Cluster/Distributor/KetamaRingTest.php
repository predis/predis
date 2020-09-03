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
class KetamaRingTest extends PredisDistributorTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getDistributorInstance(): DistributorInterface
    {
        return new KetamaRing();
    }

    /**
     * @group disconnected
     */
    public function testHash(): void
    {
        /** @var HashGeneratorInterface */
        $ring = $this->getDistributorInstance();
        list(, $hash) = unpack('V', md5('foobar', true));

        $this->assertEquals($hash, $ring->hash('foobar'));
    }

    /**
     * @group disconnected
     */
    public function testSingleNodeInRing(): void
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
    public function testMultipleNodesInRing(): void
    {
        $ring = $this->getSampleDistribution(array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        ));

        $expected = array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7002',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7002',
            '127.0.0.1:7000',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
            '127.0.0.1:7000',
            '127.0.0.1:7002',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        );

        $actual = $this->getNodes($ring, 20);

        $this->assertSame($expected, $actual);
    }

    /**
     * @group disconnected
     */
    public function testSubsequendAddAndRemoveFromRing(): void
    {
        $ring = $this->getDistributorInstance();

        $expected1 = array_fill(0, 10, '127.0.0.1:7000');
        $expected3 = array_fill(0, 10, '127.0.0.1:7001');
        $expected2 = array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7000',
            '127.0.0.1:7001',
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
    public function testGetByValue(): void
    {
        $ring = $this->getSampleDistribution(array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        ));

        $this->assertSame('127.0.0.1:7001', $ring->get('uid:256'));
        $this->assertSame('127.0.0.1:7002', $ring->get('uid:281'));
        $this->assertSame('127.0.0.1:7001', $ring->get('uid:312'));
        $this->assertSame('127.0.0.1:7000', $ring->get('uid:432'));
        $this->assertSame('127.0.0.1:7000', $ring->get('uid:500'));
        $this->assertSame('127.0.0.1:7002', $ring->get('uid:641'));
    }

    /**
     * @group disconnected
     */
    public function testGetByHash(): void
    {
        $ring = $this->getSampleDistribution(array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        ));

        $this->assertSame('127.0.0.1:7001', $ring->getByHash(PHP_INT_SIZE == 4 ?  -591277534 : 3703689762)); // uid:256
        $this->assertSame('127.0.0.1:7002', $ring->getByHash(PHP_INT_SIZE == 4 ? -1632011260 : 2662956036)); // uid:281
        $this->assertSame('127.0.0.1:7001', $ring->getByHash(PHP_INT_SIZE == 4 ?   345494622 :  345494622)); // uid:312
        $this->assertSame('127.0.0.1:7000', $ring->getByHash(PHP_INT_SIZE == 4 ? -1042625818 : 3252341478)); // uid:432
        $this->assertSame('127.0.0.1:7000', $ring->getByHash(PHP_INT_SIZE == 4 ?  -465463623 : 3829503673)); // uid:500
        $this->assertSame('127.0.0.1:7002', $ring->getByHash(PHP_INT_SIZE == 4 ?  2141928822 : 2141928822)); // uid:641
    }

    /**
     * @group disconnected
     */
    public function testGetBySlot(): void
    {
        $ring = $this->getSampleDistribution(array(
            '127.0.0.1:7000',
            '127.0.0.1:7001',
            '127.0.0.1:7002',
        ));

        $this->assertSame('127.0.0.1:7001', $ring->getBySlot(PHP_INT_SIZE == 4 ?  -585685153 : 3709282143)); // uid:256
        $this->assertSame('127.0.0.1:7002', $ring->getBySlot(PHP_INT_SIZE == 4 ? -1617239533 : 2677727763)); // uid:281
        $this->assertSame('127.0.0.1:7001', $ring->getBySlot(PHP_INT_SIZE == 4 ?   353009954 :  353009954)); // uid:312
        $this->assertSame('127.0.0.1:7000', $ring->getBySlot(PHP_INT_SIZE == 4 ? -1037794023 : 3257173273)); // uid:432
        $this->assertSame('127.0.0.1:7000', $ring->getBySlot(PHP_INT_SIZE == 4 ?  -458724341 : 3836242955)); // uid:500
        $this->assertSame('127.0.0.1:7002', $ring->getBySlot(PHP_INT_SIZE == 4 ? -2143763192 : 2151204104)); // uid:641

        // Test first and last slots
        $this->assertSame('127.0.0.1:7002', $ring->getBySlot(PHP_INT_SIZE == 4 ? -2135629153 : 2159338143));
        $this->assertSame('127.0.0.1:7000', $ring->getBySlot(PHP_INT_SIZE == 4 ?  2137506232 : 2137506232));

        // Test non-existing slot
        $this->assertNull($ring->getBySlot(0));
    }

    /**
     * @group disconnected
     */
    public function testCallbackToGetNodeHash(): void
    {
        $node = '127.0.0.1:7000';
        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();

        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($node)
            ->willReturn($node);

        $distributor = new KetamaRing($callable);
        $distributor->add($node);

        $this->getNodes($distributor);
    }
}
