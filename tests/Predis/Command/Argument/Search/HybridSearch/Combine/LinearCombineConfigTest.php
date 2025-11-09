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

class LinearCombineConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(LinearCombineConfig $config, array $expectedReturn)
    {
        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with ALPHA' => [(new LinearCombineConfig())->alpha(0.2), ['COMBINE', 'LINEAR', 2, 'ALPHA', 0.2]],
            'with BETA' => [(new LinearCombineConfig())->beta(0.2), ['COMBINE', 'LINEAR', 2, 'BETA', 0.2]],
            'with all arguments' => [
                (new LinearCombineConfig())->alpha(0.3)->beta(0.2)->as('alias'), ['COMBINE', 'LINEAR', 6, 'ALPHA', 0.3, 'BETA', 0.2, 'YIELD_SCORE_AS', 'alias'],
            ],
        ];
    }
}
