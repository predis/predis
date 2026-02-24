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

class SearchConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(
        array $expectedReturn,
        ?string $query = null,
        ?string $type = null,
        ?string $as = null
    ) {
        $config = new SearchConfig();

        if ($query) {
            $this->assertEquals($config, $config->query($query));
        }

        if ($type) {
            $this->assertEquals($config, $config->buildScorerConfig(function (ScorerConfig $scorerConfig) use ($type) {
                $scorerConfig->type($type);
            }));
        }

        if ($as) {
            $this->assertEquals($config, $config->as($as));
        }

        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with query' => [['SEARCH', '*'], '*', null, null],
            'with AS' => [['SEARCH', '*', 'YIELD_SCORE_AS', 'alias'], '*', null, 'alias'],
            'with SCORER' => [
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DOCSCORE],
                '*', ScorerConfig::TYPE_DOCSCORE, null,
            ],
        ];
    }
}
