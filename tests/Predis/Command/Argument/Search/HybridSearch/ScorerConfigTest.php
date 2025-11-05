<?php

namespace Predis\Command\Argument\Search\HybridSearch;

use PHPUnit\Framework\TestCase;

class ScorerConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(ScorerConfig $config, array $expectedReturn)
    {
        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with TYPE' => [(new ScorerConfig())->type(), [ScorerConfig::TYPE_BM25]],
            'with YIELD_SCORE_AS' => [(new ScorerConfig())->as('alias'), ['YIELD_SCORE_AS', 'alias']],
            'with all' => [
                (new ScorerConfig())->type(ScorerConfig::TYPE_DISMAX)->as('alias'),
                [ScorerConfig::TYPE_DISMAX, 'YIELD_SCORE_AS', 'alias']
            ],
        ];
    }
}
