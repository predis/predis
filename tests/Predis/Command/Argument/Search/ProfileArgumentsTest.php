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

namespace Predis\Command\Argument\Search;

use PHPUnit\Framework\TestCase;

class ProfileArgumentsTest extends TestCase
{
    /**
     * @var ProfileArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new ProfileArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithSearchModifier(): void
    {
        $this->arguments->search();

        $this->assertSame(['SEARCH'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithAggregateModifier(): void
    {
        $this->arguments->aggregate();

        $this->assertSame(['AGGREGATE'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLimitedModifier(): void
    {
        $this->arguments->limited();

        $this->assertSame(['LIMITED'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithQueryModifier(): void
    {
        $this->arguments->query('query');

        $this->assertSame(['QUERY', 'query'], $this->arguments->toArray());
    }
}
