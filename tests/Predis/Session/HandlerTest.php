<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Session;

use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;

class HandlerTest extends TestCase
{
    public function testCreatesAnUnusedSessionId()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(2))->method('__call')
            ->willReturnOnConsecutiveCalls(1, 0);

        $id = (new Handler($client))->create_sid();

        $this->assertSame(1, preg_match('/^[a-f0-9]{64}$/', $id));
    }

    public function testValidatesExistingAndMissingIds()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(2))->method('__call')
            ->with('exists', ['session-id'])
            ->willReturnOnConsecutiveCalls(1, 0);
        $handler = new Handler($client);

        $this->assertTrue($handler->validateId('session-id'));
        $this->assertFalse($handler->validateId('session-id'));
    }

    public function testUpdatesTimestampAndPreservesData()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())->method('__call')
            ->with('setex', ['session-id', 120, 'data']);
        $handler = new Handler($client, ['gc_maxlifetime' => 120]);

        $this->assertTrue($handler->updateTimestamp('session-id', 'data'));
    }
}
