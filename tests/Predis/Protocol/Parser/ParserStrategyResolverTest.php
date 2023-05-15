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

namespace Predis\Protocol\Parser;

use InvalidArgumentException;
use Predis\Protocol\Parser\Strategy\Resp2Strategy;
use Predis\Protocol\Parser\Strategy\Resp3Strategy;
use PredisTestCase;

class ParserStrategyResolverTest extends PredisTestCase
{
    /**
     * @var ParserStrategyResolverInterface
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ParserStrategyResolver();
    }

    /**
     * @dataProvider protocolProvider
     * @group disconnected
     * @param  int    $protocolVersion
     * @param  string $expectedStrategy
     * @return void
     */
    public function testResolveCorrectlyResolvesStrategyForGivenProtocolVersion(
        int $protocolVersion,
        string $expectedStrategy
    ): void {
        $this->assertInstanceOf($expectedStrategy, $this->resolver->resolve($protocolVersion));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testResolveThrowsExceptionOnUnexpectedProtocolVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid protocol version given.');

        $this->resolver->resolve(4);
    }

    public function protocolProvider(): array
    {
        return [
            'with RESP2 protocol' => [2, Resp2Strategy::class],
            'with RESP3 protocol' => [3, Resp3Strategy::class],
        ];
    }
}
