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

namespace Predis\Command\Argument\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SpellcheckArgumentsTest extends TestCase
{
    /**
     * @var SpellcheckArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new SpellcheckArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithDistanceModifier(): void
    {
        $this->arguments->distance(2);

        $this->assertSame(['DISTANCE', 2], $this->arguments->toArray());
    }

    /**
     * @dataProvider termsProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithTermsModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->terms(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnInvalidTermsModifierValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong modifier value given. Currently supports: INCLUDE, EXCLUDE');

        $this->arguments->terms('dict', 'wrong');
    }

    public function termsProvider(): array
    {
        return [
            'with INCLUDE modifier' => [
                ['dict', 'INCLUDE', 'term1', 'term2'],
                ['TERMS', 'INCLUDE', 'dict', 'term1', 'term2'],
            ],
            'with EXCLUDE modifier' => [
                ['dict', 'EXCLUDE', 'term1', 'term2'],
                ['TERMS', 'EXCLUDE', 'dict', 'term1', 'term2'],
            ],
        ];
    }
}
