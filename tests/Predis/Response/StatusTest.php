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

namespace Predis\Response;

use PredisTestCase;

class StatusTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testStatusResponse(): void
    {
        $status = new Status('OK');

        $this->assertInstanceOf('Predis\Response\ResponseInterface', $status);
        $this->assertSame('OK', $status->getPayload());
    }

    /**
     * @group disconnected
     */
    public function testStatusToString(): void
    {
        $queued = new Status('OK');

        $this->assertSame('OK', (string) $queued);
        $this->assertEquals('OK', $queued);
    }

    /**
     * @group disconnected
     */
    public function testStaticGetMethod(): void
    {
        $this->assertInstanceOf('Predis\Response\Status', $response = Status::get('OK'));
        $this->assertEquals('OK', $response);

        $this->assertInstanceOf('Predis\Response\Status', $response = Status::get('QUEUED'));
        $this->assertEquals('QUEUED', $response);

        $this->assertInstanceOf('Predis\Response\Status', $response = Status::get('PONG'));
        $this->assertEquals('PONG', $response);
    }

    /**
     * @group disconnected
     */
    public function testStaticGetMethodCachesOnlyCommonStatuses(): void
    {
        $response = Status::get('OK');
        $this->assertSame($response, Status::get('OK'));

        $response = Status::get('QUEUED');
        $this->assertSame($response, Status::get('QUEUED'));

        $response = Status::get('PONG');
        $this->assertNotSame($response, Status::get('PONG'));
    }
}
