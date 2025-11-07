<?php

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
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DOCSCORE, 'YIELD_SCORE_AS', 'alias']
            ],
        ];
    }
}
