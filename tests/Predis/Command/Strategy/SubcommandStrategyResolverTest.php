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

namespace Predis\Command\Strategy;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Predis\Command\Strategy\ContainerCommands\Functions\LoadStrategy;

class SubcommandStrategyResolverTest extends TestCase
{
    /**
     * @var StrategyResolverInterface
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SubcommandStrategyResolver();
    }

    /**
     * @return void
     */
    public function testResolveCorrectStrategy(): void
    {
        $expectedStrategy = new LoadStrategy();

        $this->assertEquals($expectedStrategy, $this->resolver->resolve('functions', 'load'));
    }

    /**
     * @return void
     */
    public function testResolveThrowsExceptionOnNonExistingStrategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-existing container command given');

        $this->resolver->resolve('foo', 'bar');
    }
}
