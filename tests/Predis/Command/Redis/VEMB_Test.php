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

namespace Predis\Command\Redis;

use Predis\Command\Redis\Utils\VectorUtility;

class VEMB_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return VEMB::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'VEMB';
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

        // Without RAW argument
        $this->assertSame([0.111], $command->parseResponse(['0.111']));

        $arguments = ['key', 'elem', true];
        $command->setArguments($arguments);

        // With RAW argument
        $this->assertSame(['0.111'], $command->parseResponse(['0.111']));
    }

    /**
     * @dataProvider quantisationProvider
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsVectorEmbedding(?string $quantisation)
    {
        $redis = $this->getClient();

        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', null, false, $quantisation)
        );

        // RAW response
        foreach ($redis->vemb('key', 'elem1', true) as $key => $value) {
            if ($key <= 1) {
                $this->assertTrue(is_string($value));
            } else {
                $this->assertTrue(is_float($value));
            }
        }

        // no RAW response
        if ($quantisation !== VADD::QUANT_BIN) {
            $this->assertEqualsWithDelta([0.1, 0.2, 0.3, 0.4], $redis->vemb('key', 'elem1'), 0.1);
        } else {
            // Binary quantisation stores any vector > 0 as 1 and < 0 as 0
            $this->assertEqualsWithDelta([1, 1, 1, 1], $redis->vemb('key', 'elem1'), 0.1);
        }
    }

    /**
     * @dataProvider quantisationProvider
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testReturnsVectorEmbeddingResp3(?string $quantisation)
    {
        $redis = $this->getResp3Client();

        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', null, false, $quantisation)
        );

        // RAW response
        foreach ($redis->vemb('key', 'elem1', true) as $key => $value) {
            if ($key <= 1) {
                $this->assertTrue(is_string($value));
            } else {
                $this->assertTrue(is_float($value));
            }
        }

        // no RAW response
        if ($quantisation !== VADD::QUANT_BIN) {
            $this->assertEqualsWithDelta([0.1, 0.2, 0.3, 0.4], $redis->vemb('key', 'elem1'), 0.1);
        } else {
            // Binary quantisation stores any vector > 0 as 1 and < 0 as 0
            $this->assertEqualsWithDelta([1, 1, 1, 1], $redis->vemb('key', 'elem1'), 0.1);
        }
    }

    public function argumentsProvider(): array
    {
        return [
            'with default args' => [
                ['key', 'elem'],
                ['key', 'elem'],
            ],
            'with RAW' => [
                ['key', 'elem', true],
                ['key', 'elem', 'RAW'],
            ],
        ];
    }

    public function quantisationProvider(): array
    {
        return [[VADD::QUANT_DEFAULT], [VADD::QUANT_Q8], [VADD::QUANT_BIN], [VADD::QUANT_NOQUANT]];
    }
}
