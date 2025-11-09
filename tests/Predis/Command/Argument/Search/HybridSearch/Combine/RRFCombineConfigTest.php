<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Search\HybridSearch\Combine;

use PHPUnit\Framework\TestCase;

class RRFCombineConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(RRFCombineConfig $config, array $expectedReturn)
    {
        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with WINDOW' => [(new RRFCombineConfig())->window(5), ['COMBINE', 'RRF', 2, 'WINDOW', 5]],
            'with CONSTANT' => [(new RRFCombineConfig())->rrfConstant(5), ['COMBINE', 'RRF', 2, 'CONSTANT', 5]],
            'with all' => [(new RRFCombineConfig())->window(10)->rrfConstant(5)->as('alias'), ['COMBINE', 'RRF', 6, 'WINDOW', 10, 'CONSTANT', 5, 'YIELD_SCORE_AS', 'alias']],
        ];
    }
}
