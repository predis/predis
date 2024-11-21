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

class IncrByArgumentsTest extends TestCase
{
    /**
     * @var IncrByArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new IncrByArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithTimestampModifier(): void
    {
        $this->arguments->timestamp('*');

        $this->assertSame(['TIMESTAMP', '*'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithUncompressedModifier(): void
    {
        $this->arguments->uncompressed();

        $this->assertSame(['UNCOMPRESSED'], $this->arguments->toArray());
    }
}
