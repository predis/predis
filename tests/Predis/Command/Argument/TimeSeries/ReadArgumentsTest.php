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

namespace Predis\Command\Argument\TimeSeries;

use PHPUnit\Framework\TestCase;

class ReadArgumentsTest extends TestCase
{
    /**
     * @var ReadArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new ReadArguments();
    }

    /**
     * @return void
     */
    public function testReturnsEmptyArgumentsByDefault(): void
    {
        $this->assertSame([], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithBlockModifier(): void
    {
        $this->arguments->block(1000, 5);

        $this->assertSame(['BLOCK', 1000, 5], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithZeroBlockTimeout(): void
    {
        $this->arguments->block(0, 1);

        $this->assertSame(['BLOCK', 0, 1], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithMaxCountModifier(): void
    {
        $this->arguments->maxCount(10);

        $this->assertSame(['MAX_COUNT', 10], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithAllModifiers(): void
    {
        $this->arguments
            ->block(1000, 5)
            ->maxCount(10);

        $this->assertSame(['BLOCK', 1000, 5, 'MAX_COUNT', 10], $this->arguments->toArray());
    }
}
