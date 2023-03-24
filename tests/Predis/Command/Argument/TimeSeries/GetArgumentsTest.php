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

namespace Predis\Command\Argument\TimeSeries;

use PHPUnit\Framework\TestCase;

class GetArgumentsTest extends TestCase
{
    /**
     * @var GetArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new GetArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithEncodingModifier(): void
    {
        $this->arguments->latest();

        $this->assertSame(['LATEST'], $this->arguments->toArray());
    }
}
