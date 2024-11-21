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

namespace Predis\Command\Redis\Container;

use Predis\ClientInterface;
use PredisTestCase;

class CLUSTER_Test extends PredisTestCase
{
    public function testGetContainerCommandId(): void
    {
        $mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $command = new CLUSTER($mockClient);

        $this->assertSame('CLUSTER', $command->getContainerCommandId());
    }
}
