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

namespace Predis\Command\Resolver;

use PHPUnit\Framework\TestCase;
use Predis\Command\Redis\SET;

class CommandResolverTest extends TestCase
{
    /**
     * @dataProvider commandsProvider
     * @param  string      $commandID
     * @param  string|null $expectedCommandClass
     * @return void
     */
    public function testResolveResolvesCorrectlyCommand(
        string $commandID,
        ?string $expectedCommandClass
    ): void {
        $resolver = new CommandResolver();

        $this->assertSame($expectedCommandClass, $resolver->resolve($commandID));
    }

    public function commandsProvider(): array
    {
        return [
            'core command exists' => ['SET', SET::class],
            'module not exist' => ['FOOBAR', null],
            'module exists, module command not exists' => ['JSONFOO', null],
        ];
    }
}
