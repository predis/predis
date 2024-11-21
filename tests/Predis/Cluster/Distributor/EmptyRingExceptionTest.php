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

namespace Predis\Cluster\Distributor;

use PredisTestCase;

class EmptyRingExceptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExceptionMessage(): void
    {
        $this->expectException('Predis\Cluster\Distributor\EmptyRingException');
        $this->expectExceptionMessage('Empty Ring');

        throw new EmptyRingException('Empty Ring');
    }
}
