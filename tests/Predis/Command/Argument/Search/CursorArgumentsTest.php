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

class CursorArgumentsTest extends TestCase
{
    /**
     * @var CursorArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new CursorArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithCountModifier(): void
    {
        $this->arguments->count(2);

        $this->assertSame(['COUNT', 2], $this->arguments->toArray());
    }
}
