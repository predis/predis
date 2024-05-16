<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Argument\Hash\HGetFArguments;

class HGETF_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return HGETF::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'HGETF';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.4.0
     */
    public function testGetHashFieldValuesWithExpiration(): void
    {
        $redis = $this->getClient();

        $this->assertSame([1, 1, 1, 1], $redis->hsetf('test',
            ['field1' => 'value1', 'field2' => 'value2', 'field3' => 'value3', 'field4' => 'value4'])
        );

        $this->assertSame(['value1', 'value2', 'value3', 'value4'], $redis->hgetf('test', ['field1', 'field2', 'field3', 'field4']));
        $this->assertSame(['value1', 'value2', 'value3', 'value4'],
            $redis->hgetf(
                'test',
                ['field1', 'field2', 'field3', 'field4'],
                (new HGetFArguments())->setExpirationModifier('nx')->setTTLModifier('px', 10))
        );
        $this->assertSame(['value3', 'value4'],
            $redis->hgetf(
                'test',
                ['field3', 'field4'],
                (new HGetFArguments())->setPersist())
        );

        usleep(15 * 1000);

        $this->assertSame(['value3', 'value4'], $redis->hgetf('test', ['field3', 'field4']));
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments' => [
                ['key', ['field1', 'field2']],
                ['key', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with expiration modifier argument - NX' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setExpirationModifier('nx')],
                ['key', 'NX', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with expiration modifier argument - XX' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setExpirationModifier('xx')],
                ['key', 'XX', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with expiration modifier argument - GT' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setExpirationModifier('gt')],
                ['key', 'GT', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with expiration modifier argument - LT' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setExpirationModifier('lt')],
                ['key', 'LT', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with TTL modifier argument - EX' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setTTLModifier('ex', 10)],
                ['key', 'EX', 10, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with TTL modifier argument - EXAT' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setTTLModifier('exat', 10)],
                ['key', 'EXAT', 10, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with TTL modifier argument - PX' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setTTLModifier('px', 10)],
                ['key', 'PX', 10, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with TTL modifier argument - PXAT' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setTTLModifier('pxat', 10)],
                ['key', 'PXAT', 10, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with TTL modifier argument - PERSIST' => [
                ['key', ['field1', 'field2'], (new HGetFArguments())->setPersist()],
                ['key', 'PERSIST', 'FIELDS', 2, 'field1', 'field2'],
            ],
        ];
    }
}
