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

namespace Predis\Command\Container;

use Predis\ClientInterface;
use PredisTestCase;

class TFUNCTION_Test extends PredisTestCase
{
    public function testGetId(): void
    {
        $mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $container = new TFUNCTION($mockClient);

        $this->assertSame('TFUNCTION', $container->getContainerCommandId());
    }
}
