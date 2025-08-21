<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Search;

use PHPUnit\Framework\TestCase;

class SugGetArgumentsTest extends TestCase
{
    /**
     * @var SugGetArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new SugGetArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithFuzzyModifier(): void
    {
        $this->arguments->fuzzy();

        $this->assertSame(['FUZZY'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithMaxModifier(): void
    {
        $this->arguments->max(5);

        $this->assertSame(['MAX', 5], $this->arguments->toArray());
    }
}
