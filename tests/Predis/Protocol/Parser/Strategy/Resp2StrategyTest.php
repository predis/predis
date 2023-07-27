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

use Predis\Protocol\Parser\UnexpectedTypeException;
use Predis\Response\ErrorInterface;
use Predis\Response\Status;
use PredisTestCase;

class Resp2StrategyTest extends PredisTestCase
{
    /**
     * @var ParserStrategyInterface
     */
    protected $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new Resp2Strategy();
    }

    /**
     * @dataProvider statusResponseProvider
     * @group disconnected
     * @param  string $statusResponse
     * @return void
     */
    public function testParseDataReturnsStatusResponseOnSimpleStringTypeStatus(string $statusResponse): void
    {
        $data = "+{$statusResponse}\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertInstanceOf(Status::class, $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsStringOnSimpleStringTypeNonStatus(): void
    {
        $data = "+string\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame('string', $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsErrorObjectOnErrorResponse(): void
    {
        $data = "-ERR unknown command 'helloworld'";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertInstanceOf(ErrorInterface::class, $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsIntegerOnIntegerResponse(): void
    {
        $data = ":1000\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(1000, $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsArrayOnArrayCountResponse(): void
    {
        $data = "*3\r\n:1\r\n:2\r\n:3\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(['type' => 'array', 'value' => 3], $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsNullOnArrayCountResponseEqualNegativeOne(): void
    {
        $data = "*-1\r\n:1\r\n:2\r\n:3\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertNull($actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsArrayOnBulkStringSizeResponse(): void
    {
        $data = "$5\r\nhello\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertSame(['type' => 'bulkString', 'value' => 5], $actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataReturnsNullOnBulkStringSizeResponseEqualNegativeOne(): void
    {
        $data = "$-1\r\nhello\r\n";

        $actualResponse = $this->strategy->parseData($data);

        $this->assertNull($actualResponse);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseDataThrowsExceptionOnUnexpectedDataTypeGiven(): void
    {
        $data = "\-1\r\nhello\r\n";

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Unexpected data type given.');

        $this->strategy->parseData($data);
    }

    public function statusResponseProvider(): array
    {
        return [
            ['OK'],
            ['QUEUED'],
            ['NOKEY'],
            ['PONG'],
        ];
    }
}
