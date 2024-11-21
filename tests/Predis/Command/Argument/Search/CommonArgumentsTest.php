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

    /**
     * @return void
     */
    public function testCreatesArgumentsWithPayloadModifier(): void
    {
        $this->arguments->payload('payload');

        $this->assertSame(['PAYLOAD', 'payload'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithStopInitialScanModifier(): void
    {
        $this->arguments->skipInitialScan();

        $this->assertSame(['SKIPINITIALSCAN'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithWithScoresModifier(): void
    {
        $this->arguments->withScores();

        $this->assertSame(['WITHSCORES'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithWithPayloadsModifier(): void
    {
        $this->arguments->withPayloads();

        $this->assertSame(['WITHPAYLOADS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithVerbatimModifier(): void
    {
        $this->arguments->verbatim();

        $this->assertSame(['VERBATIM'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithTimeoutModifier(): void
    {
        $this->arguments->timeout(2);

        $this->assertSame(['TIMEOUT', 2], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLimitModifier(): void
    {
        $this->arguments->limit(2, 2);

        $this->assertSame(['LIMIT', 2, 2], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithFilterModifier(): void
    {
        $this->arguments->filter('@age>16');

        $this->assertSame(['FILTER', '@age>16'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithParamsModifier(): void
    {
        $this->arguments->params(['name1', 'value1', 'name2', 'value2']);

        $this->assertSame(['PARAMS', 4, 'name1', 'value1', 'name2', 'value2'], $this->arguments->toArray());
    }
}
