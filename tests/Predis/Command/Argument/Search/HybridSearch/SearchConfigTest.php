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

namespace Predis\Command\Argument\Search\HybridSearch;

use PHPUnit\Framework\TestCase;

class SearchConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(SearchConfig $config, array $expectedReturn)
    {
        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with query' => [(new SearchConfig())->query('*'), ['SEARCH', '*']],
            'with AS' => [(new SearchConfig())->query('*')->as('alias'), ['SEARCH', '*', 'YIELD_SCORE_AS', 'alias']],
            'with SCORER' => [
                (new SearchConfig())
                    ->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                        $scorerConfig
                            ->type(ScorerConfig::TYPE_DOCSCORE)
                            ->as('alias');
                    })
                    ->query('*'),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DOCSCORE, 'YIELD_SCORE_AS', 'alias'],
            ],
        ];
    }
}
