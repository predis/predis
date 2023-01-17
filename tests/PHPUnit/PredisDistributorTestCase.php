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

namespace Predis\Cluster\Distributor;

use PredisTestCase;

abstract class PredisDistributorTestCase extends PredisTestCase
{
    /**
     * Returns a new instance of the tested distributor.
     *
     * @return DistributorInterface
     */
    abstract protected function getDistributorInstance(): DistributorInterface;

    /**
     * Returns a list of nodes from the hashring.
     *
     * @param DistributorInterface $distributor Distributor instance.
     * @param int                  $iterations  Number of nodes to fetch.
     *
     * @return array Nodes from the hashring.
     */
    protected function getNodes(DistributorInterface $distributor, int $iterations = 10): array
    {
        $nodes = [];

        for ($i = 0; $i < $iterations; ++$i) {
            $hash = $distributor->hash($i * $i);
            $nodes[] = $distributor->getByHash($hash);
        }

        return $nodes;
    }

    /**
     * Returns a distributor instance with the specified nodes added.
     *
     * @param array $nodes Nodes to add to the distributor.
     *
     * @return DistributorInterface
     */
    protected function getSampleDistribution(array $nodes): DistributorInterface
    {
        $distributor = $this->getDistributorInstance();

        foreach ($nodes as $node) {
            $distributor->add($node);
        }

        return $distributor;
    }

    /**
     * @group disconnected
     */
    public function testEmptyRingThrowsException(): void
    {
        $this->expectException('Predis\Cluster\Distributor\EmptyRingException');

        $distributor = $this->getDistributorInstance();
        $distributor->getByHash('nodehash');
    }

    /**
     * @group disconnected
     */
    public function testRemoveOnEmptyRingDoesNotThrowException(): void
    {
        $distributor = $this->getDistributorInstance();

        $this->assertNull($distributor->remove('node'));
    }
}
