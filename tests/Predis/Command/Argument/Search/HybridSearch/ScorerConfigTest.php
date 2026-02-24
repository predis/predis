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

namespace Predis\Command\Argument\Search\HybridSearch;

use PHPUnit\Framework\TestCase;

class ScorerConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(
        array $expectedReturn,
        ?string $type = null,
        ?string $as = null
    ) {
        $config = new ScorerConfig();

        if ($type) {
            $this->assertEquals($config, $config->type($type));
        }

        if ($as) {
            $this->assertEquals($config, $config->as($as));
        }

        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with TYPE' => [[ScorerConfig::TYPE_BM25], ScorerConfig::TYPE_BM25],
            'with YIELD_SCORE_AS' => [['YIELD_SCORE_AS', 'alias'], null, 'alias'],
            'with all' => [
                [ScorerConfig::TYPE_DISMAX, 'YIELD_SCORE_AS', 'alias'],
                ScorerConfig::TYPE_DISMAX, 'alias',
            ],
        ];
    }
}
