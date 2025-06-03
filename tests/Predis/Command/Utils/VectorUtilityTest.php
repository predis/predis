<?php

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
