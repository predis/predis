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
use UnexpectedValueException;

class BGetArgumentsTest extends TestCase
{
    /**
     * @var BGetArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new BGetArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithMinCountModifier(): void
    {
        $this->arguments->minCount(5);

        $this->assertSame(['MIN_COUNT', 5], $this->arguments->toArray());
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
    public function testCreatesArgumentsWithMinAndMaxCountModifiers(): void
    {
        $this->arguments->minCount(5)->maxCount(10);

        $this->assertSame(['MIN_COUNT', 5, 'MAX_COUNT', 10], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testMinCountThrowsExceptionOnNonPositiveValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Min count should be a positive integer');

        $this->arguments->minCount(0);
    }

    /**
     * @return void
     */
    public function testMaxCountThrowsExceptionOnNonPositiveValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Max count should be a positive integer');

        $this->arguments->maxCount(0);
    }
}
