<?php

namespace Predis\Command\Resolver;

use PHPUnit\Framework\TestCase;
use Predis\Command\Redis\SET;

class CommandResolverTest extends TestCase
{
    /**
     * @dataProvider commandsProvider
     * @param array $modules
     * @param string $commandID
     * @param string|null $expectedCommandClass
     * @return void
     */
    public function testResolveResolvesCorrectlyCommand(
        array $modules,
        string $commandID,
        ?string $expectedCommandClass
    ): void {
        $resolver = new CommandResolver($modules);

        $this->assertSame($expectedCommandClass, $resolver->resolve($commandID));
    }

    public function commandsProvider(): array
    {
        return [
            'core command exists' => [[], 'SET', SET::class],
            'module not exist' => [[['name' => 'foo', 'commandPrefix' => 'bar']], 'BARFOO', null],
            'module exists, module command not exists' => [[['name' => 'foo', 'commandPrefix' => 'bar']], 'FOOBAR', null],
        ];
    }
}
