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

namespace Predis\Command;

use PredisTestCase;

class PrefixableCommandTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class() extends PrefixableCommand {
            public function getId()
            {
                return 'test';
            }

            public function prefixKeys($prefix)
            {
                return 'test';
            }
        };
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testApplyPrefixForAllArguments(): void
    {
        $arguments = ['arg1', 'arg2', 'arg3'];
        $expectedArguments = ['prefix:arg1', 'prefix:arg2', 'prefix:arg3'];
        $prefix = 'prefix:';

        $this->testClass->setArguments($arguments);
        $this->testClass->applyPrefixForAllArguments($prefix);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testApplyPrefixForFirstArgument(): void
    {
        $arguments = ['arg1', 'arg2', 'arg3'];
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3'];
        $prefix = 'prefix:';

        $this->testClass->setArguments($arguments);
        $this->testClass->applyPrefixForFirstArgument($prefix);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @dataProvider interleavedArgumentsProvider
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testApplyPrefixForInterleavedArgument(array $actualArguments, array $expectedArguments): void
    {
        $prefix = 'prefix:';

        $this->testClass->setArguments($actualArguments);
        $this->testClass->applyPrefixForInterleavedArgument($prefix);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @dataProvider skippingLastArgumentsProvider
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testApplyPrefixSkippingLastArgument(array $actualArguments, array $expectedArguments): void
    {
        $prefix = 'prefix:';

        $this->testClass->setArguments($actualArguments);
        $this->testClass->applyPrefixSkippingLastArgument($prefix);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @dataProvider skippingFirstArgumentsProvider
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testApplyPrefixSkippingFirstArgument(array $actualArguments, array $expectedArguments): void
    {
        $prefix = 'prefix:';

        $this->testClass->setArguments($actualArguments);
        $this->testClass->applyPrefixSkippingFirstArgument($prefix);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    public function interleavedArgumentsProvider(): array
    {
        return [
            'with empty arguments' => [
                [],
                [],
            ],
            'with non-empty arguments' => [
                ['arg1', 'arg2', 'arg3', 'arg4'],
                ['prefix:arg1', 'arg2', 'prefix:arg3', 'arg4'],
            ],
        ];
    }

    public function skippingLastArgumentsProvider(): array
    {
        return [
            'with empty arguments' => [
                [],
                [],
            ],
            'with non-empty arguments' => [
                ['arg1', 'arg2', 'arg3', 'arg4'],
                ['prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'arg4'],
            ],
        ];
    }

    public function skippingFirstArgumentsProvider(): array
    {
        return [
            'with empty arguments' => [
                [],
                [],
            ],
            'with non-empty arguments' => [
                ['arg1', 'arg2', 'arg3', 'arg4'],
                ['arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'],
            ],
        ];
    }
}
