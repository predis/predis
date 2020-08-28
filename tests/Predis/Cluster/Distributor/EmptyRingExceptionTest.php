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

use PredisTestCase;

/**
 *
 */
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
