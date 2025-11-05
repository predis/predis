<?php

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
            'with ALPHA' => [(new LinearCombineConfig())->alpha(0.2), ['LINEAR', 2, 'ALPHA', 0.2]],
            'with BETA' => [(new LinearCombineConfig())->beta(0.2), ['LINEAR', 2, 'BETA', 0.2]],
            'with all arguments' => [
                (new LinearCombineConfig())->alpha(0.3)->beta(0.2), ['LINEAR', 4, 'ALPHA', 0.3, 'BETA', 0.2]
            ],
        ];
    }
}
