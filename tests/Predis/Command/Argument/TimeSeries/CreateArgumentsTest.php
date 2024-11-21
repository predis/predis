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

namespace Predis\Command\Argument\TimeSeries;

use PHPUnit\Framework\TestCase;

class CreateArgumentsTest extends TestCase
{
    /**
     * @var CreateArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new CreateArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithEncodingModifier(): void
    {
        $this->arguments->encoding(CreateArguments::ENCODING_UNCOMPRESSED);

        $this->assertSame(['ENCODING', CreateArguments::ENCODING_UNCOMPRESSED], $this->arguments->toArray());
    }
}
