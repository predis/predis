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

namespace Predis\Protocol\Parser\Strategy;

use PredisTestCase;

class Resp3StrategyTest extends PredisTestCase
{
    /**
     * @var ParserStrategyInterface
     */
    protected $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new Resp3Strategy();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsNullOnNullType(): void
    {
        $data = "_\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertNull($actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsFloatOnDoubleType(): void
    {
        $data = ",1.23\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(1.23, $actualResponse);
    }

    /**
     * @dataProvider infinityProvider
     * @group disconnected
     * @param  string $data
     * @return void
     */
    public function testParseDataReturnsFloatInfinityOnInfinityOrNegativeInfinity(string $data): void
    {
        $actualResponse = $this->strategy->parseData($data);

        $this->assertInfinite($actualResponse);
    }

    /**
     * @dataProvider booleanProvider
     * @group disconnected
     * @param  string $data
     * @param  bool   $expectedValue
     * @return void
     */
    public function testParseDataReturnsBooleanOnBooleanType(string $data, bool $expectedValue): void
    {
        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame($expectedValue, $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsArrayOnBlobErrorType(): void
    {
        $data = "!21\r\nSYNTAX invalid syntax\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(['type' => 'blobError', 'value' => 21], $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsArrayOnVerbatimStringType(): void
    {
        $data = "=15\r\ntxt:Some string\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(['type' => 'verbatimString', 'value' => 15], $actualResponse);
    }

    /**
     * @dataProvider bigNumberProvider
     * @group disconnected
     * @param  string    $data
     * @param  int|float $expectedValue
     * @return void
     */
    public function testParseDataReturnsIntegerOrFloatOnBigNumberType(string $data, $expectedValue): void
    {
        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame($expectedValue, $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsArrayOnMapType(): void
    {
        $data = "%2\r\n+first\r\n:1\r\n+second\r\n:2";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(['type' => 'map', 'value' => 2], $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsArrayOnSetType(): void
    {
        $data = "~4\r\n+first\r\n:1\r\n+second\r\n:2";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(['type' => 'set', 'value' => 4], $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsArrayOnPushType(): void
    {
        $data = ">4\r\n+pubsub\r\n+message\r\n+somechannel\r\n+this is the message";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(['type' => 'push', 'value' => 4], $actualResponse);
    }

    public function infinityProvider(): array
    {
        return [
            'positive infinity' => [",inf\r\n"],
            'negative infinity' => [",-inf\r\n"],
        ];
    }

    public function booleanProvider(): array
    {
        return [
            'true' => ["#t\r\n", true],
            'false' => ["#f\r\n", false],
        ];
    }

    public function bigNumberProvider(): array
    {
        return [
            'greater than integer limit' => [
                "(3492890328409238509324850943850943825024385\r\n",
                3492890328409238509324850943850943825024385,
            ],
            'lower than integer limit' => [
                "(34928903\r\n",
                34928903,
            ],
        ];
    }
}
