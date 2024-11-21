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

namespace Predis\Command\Strategy;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Predis\Command\Strategy\ContainerCommands\Functions\LoadStrategy;

class SubcommandStrategyResolverTest extends TestCase
{
    /**
     * @group disconnected
     * @return void
     */
    public function testResolveCorrectStrategy(): void
    {
        $resolver = new SubcommandStrategyResolver();
        $expectedStrategy = new LoadStrategy();

        $this->assertEquals($expectedStrategy, $resolver->resolve('functions', 'load'));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testResolveCorrectlyResolvesStrategyWithGivenWordSeparator(): void
    {
        $resolver = new SubcommandStrategyResolver('_');
        $expectedStrategy = new LoadStrategy();

        $this->assertEquals($expectedStrategy, $resolver->resolve('functions_', 'load_'));
    }

    /**
     * @return void
     */
    public function testResolveThrowsExceptionOnNonExistingStrategy(): void
    {
        $resolver = new SubcommandStrategyResolver();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-existing container command given');

        $resolver->resolve('foo', 'bar');
    }
}
