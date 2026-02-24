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
use UnexpectedValueException;

class VADD_Test extends PredisCommandTestCase
{
    /**
     * @return string
     */
    protected function getExpectedCommand(): string
    {
        return VADD::class;
    }

    /**
     * @return string
     */
    protected function getExpectedId(): string
    {
        return 'VADD';
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
    public function testFilterArgumentsThrowsUnexpectedValueException(): void
    {
        $command = $this->getCommand();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Vector should be rather 32 bit floating blob or array of floatings');
        $command->setArguments(['key', 1000, 'elem']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Attributes arguments should be a JSON string or associative array');
        $command->setArguments(['key', [0.1, 0.2, 0.3], 'elem', null, false, VADD::QUANT_DEFAULT, null, 1000]);
    }

    /**
     * @return void
     */
    public function testParseResponse(): void
    {
        $this->assertTrue($this->getCommand()->parseResponse(1));
        $this->assertFalse($this->getCommand()->parseResponse(0));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testAddVectorsIntoVectorSet()
    {
        $redis = $this->getClient();

        // Vector as blob string
        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10)
        );
        // Vector as array
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem2', 10)
        );
        // With CAS
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem3', 10, true)
        );
        // With quantisation
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem4', 10, true, VADD::QUANT_Q8)
        );
        // With attributes as JSON
        $this->assertTrue(
            $redis->vadd(
                'key', [0.1, 0.2, 0.3, 0.4], 'elem5', 10, true, VADD::QUANT_Q8, null,
                '{"key1":"value1","key2":"value2"}'
            )
        );
        // With attributes as associative array
        $this->assertTrue(
            $redis->vadd(
                'key', [0.1, 0.2, 0.3, 0.4], 'elem6', 10, true, VADD::QUANT_Q8, null,
                ['key1' => 'value1', 'key2' => 'value2']
            )
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.0.0
     */
    public function testAddVectorsIntoVectorSetResp3()
    {
        $redis = $this->getResp3Client();

        // Vector as blob string
        $this->assertTrue(
            $redis->vadd('key', VectorUtility::toBlob([0.1, 0.2, 0.3, 0.4]), 'elem1', 10)
        );
        // Vector as array
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem2', 10)
        );
        // With CAS
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem3', 10, true)
        );
        // With quantisation
        $this->assertTrue(
            $redis->vadd('key', [0.1, 0.2, 0.3, 0.4], 'elem4', 10, true, VADD::QUANT_Q8)
        );
        // With attributes as JSON
        $this->assertTrue(
            $redis->vadd(
                'key', [0.1, 0.2, 0.3, 0.4], 'elem5', 10, true, VADD::QUANT_Q8, null,
                '{"key1":"value1","key2":"value2"}'
            )
        );
        // With attributes as associative array
        $this->assertTrue(
            $redis->vadd(
                'key', [0.1, 0.2, 0.3, 0.4], 'elem6', 10, true, VADD::QUANT_Q8, null,
                ['key1' => 'value1', 'key2' => 'value2']
            )
        );
    }

    public function argumentsProvider(): array
    {
        return [
            'with default args - blob string' => [
                ['key', VectorUtility::toBlob([0.1, 0.2, 0.3]), 'elem'],
                ['key', 'FP32', VectorUtility::toBlob([0.1, 0.2, 0.3]), 'elem'],
            ],
            'with default args - floatings array' => [
                ['key', [0.1, 0.2, 0.3], 'elem'],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem'],
            ],
            'with REDUCE' => [
                ['key', [0.1, 0.2, 0.3], 'elem', 10],
                ['key', 'REDUCE', 10, 'VALUES', 3, 0.1, 0.2, 0.3, 'elem'],
            ],
            'with CAS' => [
                ['key', [0.1, 0.2, 0.3], 'elem', null, true],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem', 'CAS'],
            ],
            'with quantisation - NOQUANT' => [
                ['key', [0.1, 0.2, 0.3], 'elem', null, false, VADD::QUANT_NOQUANT],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem', 'NOQUANT'],
            ],
            'with quantisation - BIN' => [
                ['key', [0.1, 0.2, 0.3], 'elem', null, false, VADD::QUANT_BIN],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem', 'BIN'],
            ],
            'with quantisation - Q8' => [
                ['key', [0.1, 0.2, 0.3], 'elem', null, false, VADD::QUANT_Q8],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem', 'Q8'],
            ],
            'with EF' => [
                ['key', [0.1, 0.2, 0.3], 'elem', null, false, VADD::QUANT_DEFAULT, 10],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem', 'EF', 10],
            ],
            'with SETATTR - JSON string' => [
                [
                    'key', [0.1, 0.2, 0.3], 'elem', null, false, VADD::QUANT_DEFAULT, null,
                    '{"key1":"value1","key2":"value2"}',
                ],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem', 'SETATTR', '{"key1":"value1","key2":"value2"}'],
            ],
            'with SETATTR - associative array' => [
                [
                    'key', [0.1, 0.2, 0.3], 'elem', null, false, VADD::QUANT_DEFAULT, null,
                    ['key1' => 'value1', 'key2' => 'value2'],
                ],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem', 'SETATTR', '{"key1":"value1","key2":"value2"}'],
            ],
            'with numlinks' => [
                [
                    'key', [0.1, 0.2, 0.3], 'elem', null, false, VADD::QUANT_DEFAULT, null, null, 10,
                ],
                ['key', 'VALUES', 3, 0.1, 0.2, 0.3, 'elem', 'M', 10],
            ],
        ];
    }
}
