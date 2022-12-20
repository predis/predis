<?php

namespace Predis\Command\Resolver;

use PHPUnit\Framework\TestCase;
use Predis\Command\Redis\SET;

class CommandResolverTest extends TestCase
{
    /**
     * @dataProvider commandsProvider
     * @param string $commandID
     * @param string|null $expectedCommandClass
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
