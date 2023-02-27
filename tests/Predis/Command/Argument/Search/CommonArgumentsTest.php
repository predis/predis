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

namespace Predis\Command\Argument\Search;

use PHPUnit\Framework\TestCase;

class CommonArgumentsTest extends TestCase
{
    /**
     * @var CommonArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new CommonArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLanguageModifier(): void
    {
        $this->arguments->language('english');

        $this->assertSame(['LANGUAGE', 'english'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithDialectModifier(): void
    {
        $this->arguments->dialect('dialect');

        $this->assertSame(['DIALECT', 'dialect'], $this->arguments->toArray());
    }
}
