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

class BITFIELD_RO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return BITFIELD_RO::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'BITFIELD_RO';
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
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnBitsOfSpecificString()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame([6, 98], $redis->bitfield_ro('foo', ['u4' => 0, 'i8' => 0]));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with single encoding-offset entry' => [
                ['key', ['encoding' => 'offset']],
                ['key', 'GET', 'encoding', 'offset'],
            ],
            'with multiple encoding-offset entry' => [
                ['key', ['encoding' => 'offset', 'encoding1' => 'offset1', 'encoding2' => 'offset2']],
                ['key', 'GET', 'encoding', 'offset', 'GET', 'encoding1', 'offset1', 'GET', 'encoding2', 'offset2'],
            ],
        ];
    }
}
