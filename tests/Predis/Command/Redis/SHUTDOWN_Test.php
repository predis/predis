<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-server
 */
class SHUTDOWN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SHUTDOWN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SHUTDOWN';
    }

    /**
     * @dataProvider argumentsProvider
     * @group disconnected
     */
    public function testFilterArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedResponse, $command->getArguments());
    }

    public function argumentsProvider(): array
    {
        return [
            'with no arguments' => [
                [],
                [],
            ],
            'with SAVE argument' => [
                [true],
                ['SAVE'],
            ],
            'with NOSAVE argument' => [
                [false],
                ['NOSAVE'],
            ],
            'with NOW argument' => [
                [null, true],
                ['NOW'],
            ],
            'with FORCE argument' => [
                [null, false, true],
                ['FORCE'],
            ],
            'with ABORT argument' => [
                [null, false, false, true],
                ['ABORT'],
            ],
            'with all arguments' => [
                [true, true, true, true],
                ['SAVE', 'NOW', 'FORCE', 'ABORT'],
            ],
        ];
    }
}
