<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Utils;

use Predis\Command\Redis\Utils\VectorUtility;
use PredisTestCase;

class VectorUtilityTest extends PredisTestCase
{
    /**
     * @return void
     */
    public function testToBlob()
    {
        $this->assertSame(
            pack('f*', 0.1, 0.2, 0.3),
            VectorUtility::toBlob([0.1, 0.2, 0.3])
        );
    }

    /**
     * @return void
     */
    public function testToArray()
    {
        $this->assertSame(
            unpack('f*', VectorUtility::toBlob([0.1, 0.2, 0.3])),
            VectorUtility::toArray(VectorUtility::toBlob([0.1, 0.2, 0.3]))
        );
    }
}
