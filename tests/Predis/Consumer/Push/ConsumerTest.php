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

namespace Predis\Consumer\Push;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\ClientInterface;
use Predis\Connection\NodeConnectionInterface;
use PredisTestCase;

class ConsumerTest extends PredisTestCase
{
    /**
     * @var MockObject&ClientInterface&MockObject
     */
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->getMockBuilder(ClientInterface::class)->getMock();
    }

    /**
     * @dataProvider responseProvider
     * @group disconnected
     * @param       $readData
     * @param       $expectedResponse
     * @return void
     */
    public function testCurrentReturnsResponseFromServer($readData, $expectedResponse): void
    {
        $mockConnection = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $mockConnection
            ->expects($this->once())
            ->method('read')
            ->withAnyParameters()
            ->willReturn($readData);

        $this->mockClient
            ->expects($this->once())
            ->method('getConnection')
            ->withAnyParameters()
            ->willReturn($mockConnection);

        $consumer = new Consumer($this->mockClient);

        $this->assertSame($expectedResponse, $consumer->current());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testConstructCallsGivenCallbackOnObjectInstantiation(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('disconnect')
            ->withAnyParameters();

        $callback = static function (ClientInterface $client) {
            $client->disconnect();
        };

        new Consumer($this->mockClient, $callback);
    }

    public function responseProvider(): array
    {
        $pushResponse = new PushResponse(['messageType', 'payload']);

        return [
            'with push response' => [$pushResponse, $pushResponse],
            'with another response' => ['string', null],
        ];
    }
}
