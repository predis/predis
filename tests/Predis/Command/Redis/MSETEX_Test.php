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

use ValueError;

class MSETEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return MSETEX::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'MSETEX';
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
     * @requiresRedisVersion >= 8.3.224
     */
    public function testSetWithModifiers()
    {
        $redis = $this->getClient();

        $this->assertEquals(1, $redis->msetex(['foo' => 'bar', 'bar' => 'baz'], 'nx'));
        $this->assertEquals(0, $redis->msetex(['foo' => 'bar', 'bar' => 'baz'], 'nx'));
        $this->assertEquals(1, $redis->msetex(['foo' => 'baz', 'bar' => 'bar'], 'xx'));
        $this->assertEquals(0, $redis->msetex(['foo' => 'baz', 'baz' => 'bar'], 'xx'));

        $this->assertEquals(1, $redis->msetex(['foo' => 'baz', 'bar' => 'bar'], null, 'ex', 10));
        $this->assertEquals(1, $redis->msetex(['foo' => 'baz', 'bar' => 'bar'], null, 'px', 1000));
        $this->assertEquals(1, $redis->msetex(['foo' => 'baz', 'bar' => 'bar'], null, 'exat', time() + 10));
        $this->assertEquals(1, $redis->msetex(['foo' => 'baz', 'bar' => 'bar'], null, 'pxat', (time() * 1000) + 1000));
        $this->assertEquals(1, $redis->msetex(['foo' => 'baz', 'bar' => 'bar'], null, 'keepttl'));

        $this->assertGreaterThan(0, $redis->expiretime('foo'));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testThrowsExceptionOnInvalidArguments(): void
    {
        $command = $this->getCommand();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Incorrect exist modifier. Should be one of: NX, XX.');

        $command->setArguments([['key' => 'value'], 'wrong']);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('TTL should be specified along with expire resolution parameter');

        $command->setArguments([['key' => 'value'], null, 'EX']);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Incorrect expire modifier. Should be one of: EX, PX, EXAT, PXAT, KEEPTTL');

        $command->setArguments([['key' => 'value'], null, 'wrong', 10]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                [['field1' => 'value1', 'field2' => 'value2']],
                [2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with exist modifier - NX' => [
                [['field1' => 'value1', 'field2' => 'value2'], 'nx'],
                [2, 'field1', 'value1', 'field2', 'value2', 'NX'],
            ],
            'with exist modifier - XX' => [
                [['field1' => 'value1', 'field2' => 'value2'], 'xx'],
                [2, 'field1', 'value1', 'field2', 'value2', 'XX'],
            ],
            'with expire modifier - EX' => [
                [['field1' => 'value1', 'field2' => 'value2'], null, 'EX', 10],
                [2, 'field1', 'value1', 'field2', 'value2', 'EX', 10],
            ],
            'with expire modifier - PX' => [
                [['field1' => 'value1', 'field2' => 'value2'], null, 'PX', 10],
                [2, 'field1', 'value1', 'field2', 'value2', 'PX', 10],
            ],
            'with expire modifier - EXAT' => [
                [['field1' => 'value1', 'field2' => 'value2'], null, 'EXAT', 10],
                [2, 'field1', 'value1', 'field2', 'value2', 'EXAT', 10],
            ],
            'with expire modifier - PXAT' => [
                [['field1' => 'value1', 'field2' => 'value2'], null, 'PXAT', 10],
                [2, 'field1', 'value1', 'field2', 'value2', 'PXAT', 10],
            ],
            'with expire modifier - KEETTL' => [
                [['field1' => 'value1', 'field2' => 'value2'], null, 'KEEPTTL'],
                [2, 'field1', 'value1', 'field2', 'value2', 'KEEPTTL'],
            ],
            'with all modifiers' => [
                [['field1' => 'value1', 'field2' => 'value2'], 'xx', 'KEEPTTL'],
                [2, 'field1', 'value1', 'field2', 'value2', 'XX', 'KEEPTTL'],
            ],
        ];
    }
}
