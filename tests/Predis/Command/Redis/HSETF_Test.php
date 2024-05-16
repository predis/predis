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

use Predis\Command\Argument\Hash\HSetFArguments;

class HSETF_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return HSETF::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'HSETF';
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
    public function testSetHashFieldsWithExpiration(): void
    {
        $redis = $this->getClient();

        $this->assertSame([1, 1], $redis->hsetf('test', ['field1' => 'value1', 'field2' => 'value2']));
        $this->assertNull(
            $redis->hsetf(
                'non_existing',
                ['field1' => 'value1', 'field2' => 'value2'],
                (new HSetFArguments())->setDontCreate())
        );
        $this->assertSame(
            [0, 0],
            $redis->hsetf(
                'test',
                ['field1' => 'value1', 'field2' => 'value2'],
                (new HSetFArguments())->setFieldModifier('dof')
            )
        );
        $this->assertSame(
            ['new_value1', 'new_value2'],
            $redis->hsetf(
                'test',
                ['field1' => 'new_value1', 'field2' => 'new_value2'],
                (new HSetFArguments())->setGetModifier('getnew')
            )
        );
        $this->assertSame(
            ['new_value1', 'new_value2'],
            $redis->hsetf(
                'test',
                ['field1' => 'recent_value1', 'field2' => 'recent_value2'],
                (new HSetFArguments())->setGetModifier('getold')
            )
        );
        $this->assertSame(
            [3, 3],
            $redis->hsetf(
                'test',
                ['field1' => 'value1', 'field2' => 'value2'],
                (new HSetFArguments())->setTTLModifier('px', 10)
            )
        );

        usleep(15 * 1000);

        $this->assertSame([null, null], $redis->hgetf('test', ['field1', 'field2']));
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2']],
                ['key', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with DC argument' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setDontCreate()],
                ['key', 'DC', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with DCF|DOF argument - DCF' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setFieldModifier('dcf')],
                ['key', 'DCF', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with field modifier argument - DOF' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setFieldModifier('dof')],
                ['key', 'DOF', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with expiration modifier argument - NX' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setExpirationModifier('nx')],
                ['key', 'NX', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with expiration modifier argument - XX' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setExpirationModifier('xx')],
                ['key', 'XX', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with expiration modifier argument - GT' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setExpirationModifier('gt')],
                ['key', 'GT', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with expiration modifier argument - LT' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setExpirationModifier('lt')],
                ['key', 'LT', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with get modifier argument - GETNEW' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setGetModifier('getnew')],
                ['key', 'GETNEW', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with get modifier argument - GETOLD' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setGetModifier('getold')],
                ['key', 'GETOLD', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with TTL modifier argument - EX' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setTTLModifier('ex', 10)],
                ['key', 'EX', 10, 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with TTL modifier argument - EXAT' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setTTLModifier('exat', 10)],
                ['key', 'EXAT', 10, 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with TTL modifier argument - PX' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setTTLModifier('px', 10)],
                ['key', 'PX', 10, 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with TTL modifier argument - PXAT' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->setTTLModifier('pxat', 10)],
                ['key', 'PXAT', 10, 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with TTL modifier argument - KEEPTTL' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], (new HSetFArguments())->enableKeepTTL()],
                ['key', 'KEEPTTL', 'FVS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
        ];
    }
}
