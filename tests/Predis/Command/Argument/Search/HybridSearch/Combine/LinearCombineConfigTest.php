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

class LinearCombineConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(
        array $expectedReturn,
        ?float $alpha = null,
        ?float $beta = null,
        ?string $as = null
    ) {
        $config = new LinearCombineConfig();

        if ($alpha) {
            $this->assertEquals($config, $config->alpha($alpha));
        }

        if ($beta) {
            $this->assertEquals($config, $config->beta($beta));
        }

        if ($as) {
            $this->assertEquals($config, $config->as($as));
        }

        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with ALPHA' => [['COMBINE', 'LINEAR', 2, 'ALPHA', 0.2], 0.2, null, null],
            'with BETA' => [['COMBINE', 'LINEAR', 2, 'BETA', 0.2], null, 0.2, null],
            'with all arguments' => [
                ['COMBINE', 'LINEAR', 6, 'ALPHA', 0.3, 'BETA', 0.2, 'YIELD_SCORE_AS', 'alias'],
                0.3, 0.2, 'alias',
            ],
        ];
    }
}
