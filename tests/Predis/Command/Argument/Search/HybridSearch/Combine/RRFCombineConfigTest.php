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

namespace Predis\Command\Argument\Search\HybridSearch\Combine;

use PHPUnit\Framework\TestCase;

class RRFCombineConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(
        array $expectedReturn,
        ?int $window = null,
        ?int $rrfConstant = null,
        ?string $as = null
    ) {
        $config = new RRFCombineConfig();

        if ($window) {
            $this->assertEquals($config, $config->window($window));
        }

        if ($rrfConstant) {
            $this->assertEquals($config, $config->rrfConstant($rrfConstant));
        }

        if ($as) {
            $this->assertEquals($config, $config->as($as));
        }

        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with WINDOW' => [['COMBINE', 'RRF', 2, 'WINDOW', 5], 5, null, null],
            'with CONSTANT' => [['COMBINE', 'RRF', 2, 'CONSTANT', 5], null, 5, null],
            'with all' => [['COMBINE', 'RRF', 6, 'WINDOW', 10, 'CONSTANT', 5, 'YIELD_SCORE_AS', 'alias'], 10, 5, 'alias'],
        ];
    }
}
