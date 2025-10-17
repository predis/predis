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

namespace Predis\Command\Redis;

use Predis\Command\Redis\Utils\VectorUtility;

class VSIM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return VSIM::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'VSIM';
    }

    /**
     * @dataProvider argumentsProvider
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @return void
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        // no scores
        $this->assertSame(['elem1', 'elem2'], $command->parseResponse(['elem1', 'elem2']));

        // with scores
        $command->setArguments(['key', [0.1, 0.2], false, true]);
        $this->assertSame(
            ['elem1' => 0.1111, 'elem2' => 0.2222],
            $command->parseResponse(['elem1', '0.1111', 'elem2', '0.2222'])
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsSimilarVectorsFromVectorSet(): void
    {
        $redis = $this->getClient();

        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1'
            )
        );
        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.9, 0.8, 0.7, 0.6]), 'elem2'
            )
        );
        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.5, 0.6, 0.7, 0.8]), 'elem3'
            )
        );

        // Find by elem
        $this->assertSame(['elem2', 'elem3', 'elem1'], $redis->vsim('key', 'elem2', true));

        // Find by blob vector
        $this->assertSame(
            ['elem2', 'elem3', 'elem1'],
            $redis->vsim('key', VectorUtility::toBlob([0.9, 0.8, 0.7, 0.6]))
        );

        // Find by array vector
        $this->assertSame(
            ['elem2', 'elem3', 'elem1'],
            $redis->vsim('key', [0.9, 0.8, 0.7, 0.6])
        );

        // With scores
        $this->assertEqualsWithDelta(
            ['elem2' => 1, 'elem3' => 0.99, 'elem1' => 0.99],
            $redis->vsim('key', 'elem2', true, true),
            0.07
        );

        // Reduce count
        $this->assertSame(
            ['elem2', 'elem3'],
            $redis->vsim('key', [0.9, 0.8, 0.7, 0.6], false, false, 2)
        );

        // Add attributes for filtering
        $this->assertTrue($redis->vsetattr('key', 'elem2', ['years' => 20]));
        $this->assertTrue($redis->vsetattr('key', 'elem3', ['years' => 16]));
        $this->assertTrue($redis->vsetattr('key', 'elem1', ['years' => 19]));

        // with Filter expression and Epsilon
        $this->assertSame(
            ['elem2', 'elem1'],
            $redis->vsim(
                'key', [0.9, 0.8, 0.7, 0.6], false, false, null, 0.2, null,
                '.years >= 18'
            )
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsSimilarVectorsFromVectorSetResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1'
            )
        );
        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.9, 0.8, 0.7, 0.6]), 'elem2'
            )
        );
        $this->assertTrue(
            $redis->vadd(
                'key', VectorUtility::toBlob([0.5, 0.6, 0.7, 0.8]), 'elem3'
            )
        );

        // Find by elem
        $this->assertSame(['elem2', 'elem3', 'elem1'], $redis->vsim('key', 'elem2', true));

        // Find by blob vector
        $this->assertSame(
            ['elem2', 'elem3', 'elem1'],
            $redis->vsim('key', VectorUtility::toBlob([0.9, 0.8, 0.7, 0.6]))
        );

        // Find by array vector
        $this->assertSame(
            ['elem2', 'elem3', 'elem1'],
            $redis->vsim('key', [0.9, 0.8, 0.7, 0.6])
        );

        // With scores
        $this->assertEqualsWithDelta(
            ['elem2' => 1, 'elem3' => 0.99, 'elem1' => 0.99],
            $redis->vsim('key', 'elem2', true, true),
            0.07
        );

        // Reduce count
        $this->assertSame(
            ['elem2', 'elem3'],
            $redis->vsim('key', [0.9, 0.8, 0.7, 0.6], false, false, 2)
        );

        // Add attributes for filtering
        $this->assertTrue($redis->vsetattr('key', 'elem2', ['years' => 20]));
        $this->assertTrue($redis->vsetattr('key', 'elem3', ['years' => 16]));
        $this->assertTrue($redis->vsetattr('key', 'elem1', ['years' => 19]));

        // with Filter expression and Epsilon
        $this->assertSame(
            ['elem2', 'elem1'],
            $redis->vsim(
                'key', [0.9, 0.8, 0.7, 0.6], false, false, null, 0.2, null,
                '.years >= 18'
            )
        );
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments - vector as array' => [
                ['key', [0.1, 0.2, 0.3, 0.4]],
                ['key', 'VALUES', 4, 0.1, 0.2, 0.3, 0.4],
            ],
            'with default arguments - vector as blob' => [
                ['key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4])],
                ['key', 'FP32', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4])],
            ],
            'with default arguments - elem' => [
                ['key', 'elem1', true],
                ['key', 'ELE', 'elem1'],
            ],
            'with WITHSCORES' => [
                ['key', 'elem1', true, true],
                ['key', 'ELE', 'elem1', 'WITHSCORES'],
            ],
            'with COUNT' => [
                ['key', 'elem1', true, false, 10],
                ['key', 'ELE', 'elem1', 'COUNT', 10],
            ],
            'with EPSILON' => [
                ['key', 'elem1', true, false, null, 0.01],
                ['key', 'ELE', 'elem1', 'EPSILON', 0.01],
            ],
            'with EF' => [
                ['key', 'elem1', true, false, null, null, 50],
                ['key', 'ELE', 'elem1', 'EF', 50],
            ],
            'with FILTER' => [
                ['key', 'elem1', true, false, null, null, null, '.year >= 1980 and .rating > 7'],
                ['key', 'ELE', 'elem1', 'FILTER', '.year >= 1980 and .rating > 7'],
            ],
            'with FILTER-EF' => [
                ['key', 'elem1', true, false, null, null, null, null, 50],
                ['key', 'ELE', 'elem1', 'FILTER-EF', 50],
            ],
            'with TRUTH' => [
                ['key', 'elem1', true, false, null, null, null, null, null, true],
                ['key', 'ELE', 'elem1', 'TRUTH'],
            ],
            'with NOTHREAD' => [
                ['key', 'elem1', true, false, null, null, null, null, null, false, true],
                ['key', 'ELE', 'elem1', 'NOTHREAD'],
            ],
        ];
    }
}
