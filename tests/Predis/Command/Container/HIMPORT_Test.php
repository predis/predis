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

namespace Predis\Command\Container;

use ArrayIterator;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Predis\Command\CommandInterface;
use Predis\Configuration\Options;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\Error;
use Predis\Response\ServerException;
use Predis\Response\Status;

class HIMPORT_Test extends TestCase
{
    /**
     * @var MockObject&ClientInterface
     */
    private $client;

    /**
     * @var Options
     */
    private $options;

    protected function setUp(): void
    {
        $this->options = new Options();
        $this->client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->client->method('getOptions')->willReturn($this->options);
        $this->client
            ->method('createCommand')
            ->willReturnCallback(function ($commandID, $arguments = []) {
                return $this->createCommandStub($arguments);
            });
    }

    /**
     * @group disconnected
     */
    public function testGetContainerCommandId(): void
    {
        $this->assertSame('HIMPORT', $this->createContainer()->getContainerCommandId());
    }

    /**
     * @group disconnected
     */
    public function testPrepareExecutesAndRecordsRegistry(): void
    {
        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $this->client->method('getConnection')->willReturn($node);
        $this->client->expects($this->once())->method('executeCommand')->willReturn(Status::get('OK'));

        $container = $this->createContainer();
        $response = $container->prepare('shared', ['name', 'email']);

        $this->assertEquals(Status::get('OK'), $response);
        $this->assertSame(['name', 'email'], $this->options->himport->getRegistry()->get('shared'));
    }

    /**
     * @group disconnected
     */
    public function testPrepareRejectsEmptyFields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createContainer()->prepare('shared', []);
    }

    /**
     * @group disconnected
     */
    public function testSetRejectsEmptyValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createContainer()->set('key', 'shared', []);
    }

    /**
     * @group disconnected
     */
    public function testSetRecoversFromNoSuchFieldsetAndRetriesOnce(): void
    {
        $this->options->himport->getRegistry()->set('shared', ['name', 'email']);

        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $node->expects($this->once())->method('executeCommand')->willReturn(Status::get('OK'));
        $this->client->method('getConnection')->willReturn($node);

        $attempts = 0;
        $this->client
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->willReturnCallback(function () use (&$attempts) {
                if (0 === $attempts++) {
                    throw new ServerException('ERR no such fieldset');
                }

                return Status::get('OK');
            });

        $response = $this->createContainer()->set('shared:1', 'shared', ['alice', 'alice@example.com']);

        $this->assertEquals(Status::get('OK'), $response);
    }

    /**
     * @group disconnected
     */
    public function testSetRecoveryIsBoundedToASingleRetry(): void
    {
        $this->options->himport->getRegistry()->set('shared', ['name']);

        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $node->method('executeCommand')->willReturn(Status::get('OK'));
        $this->client->method('getConnection')->willReturn($node);

        $this->client
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->willThrowException(new ServerException('ERR no such fieldset'));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('no such fieldset');

        $this->createContainer()->set('shared:1', 'shared', ['alice']);
    }

    /**
     * @group disconnected
     */
    public function testSetDoesNotRecoverWithoutRegistryEntry(): void
    {
        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $this->client->method('getConnection')->willReturn($node);

        $this->client
            ->expects($this->once())
            ->method('executeCommand')
            ->willThrowException(new ServerException('ERR no such fieldset'));

        $this->expectException(ServerException::class);

        $this->createContainer()->set('shared:1', 'shared', ['alice']);
    }

    /**
     * @group disconnected
     */
    public function testSetDoesNotRecoverWhenAutoPrepareDisabled(): void
    {
        $this->options = new Options(['himport' => ['auto_prepare' => false]]);
        $this->client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->client->method('getOptions')->willReturn($this->options);
        $this->client
            ->method('createCommand')
            ->willReturnCallback(function ($commandID, $arguments = []) {
                return $this->createCommandStub($arguments);
            });
        $this->options->himport->getRegistry()->set('shared', ['name']);

        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $this->client->method('getConnection')->willReturn($node);
        $this->client
            ->expects($this->once())
            ->method('executeCommand')
            ->willThrowException(new ServerException('ERR no such fieldset'));

        $this->expectException(ServerException::class);

        $this->createContainer()->set('shared:1', 'shared', ['alice']);
    }

    /**
     * @group disconnected
     */
    public function testSetRecoversFromErrorResponseWhenExceptionsDisabled(): void
    {
        $this->options = new Options(['exceptions' => false]);
        $this->client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $this->client->method('getOptions')->willReturn($this->options);
        $this->client
            ->method('createCommand')
            ->willReturnCallback(function ($commandID, $arguments = []) {
                return $this->createCommandStub($arguments);
            });
        $this->options->himport->getRegistry()->set('shared', ['name']);

        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $node->method('executeCommand')->willReturn(Status::get('OK'));
        $this->client->method('getConnection')->willReturn($node);

        $attempts = 0;
        $this->client
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->willReturnCallback(function () use (&$attempts) {
                return 0 === $attempts++ ? new Error('ERR no such fieldset') : Status::get('OK');
            });

        $response = $this->createContainer()->set('shared:1', 'shared', ['alice']);

        $this->assertEquals(Status::get('OK'), $response);
    }

    /**
     * @group disconnected
     */
    public function testPrepareFansOutToAllMasters(): void
    {
        [$node1, $node2] = $this->twoNodes();
        $node1->expects($this->once())->method('executeCommand')->willReturn(Status::get('OK'));
        $node2->expects($this->once())->method('executeCommand')->willReturn(Status::get('OK'));

        $this->client->method('getConnection')->willReturn($this->clusterOf($node1, $node2));

        $response = $this->createContainer()->prepare('shared', ['name']);

        $this->assertEquals(Status::get('OK'), $response);
        $this->assertTrue($this->options->himport->getRegistry()->has('shared'));
    }

    /**
     * @group disconnected
     */
    public function testPrepareFanOutSurfacesFirstErrorAndKeepsRegistry(): void
    {
        [$node1, $node2] = $this->twoNodes();
        $node1->method('executeCommand')->willReturn(Status::get('OK'));
        $node2->method('executeCommand')->willReturn(new Error('ERR duplicate field name in fieldset'));

        $this->client->method('getConnection')->willReturn($this->clusterOf($node1, $node2));

        try {
            $this->createContainer()->prepare('shared', ['name', 'name']);
            $this->fail('Expected ServerException was not thrown');
        } catch (ServerException $exception) {
            $this->assertStringContainsString('duplicate field name', $exception->getMessage());
        }

        // Registry keeps the entry so lagging nodes self-heal on their first SET.
        $this->assertTrue($this->options->himport->getRegistry()->has('shared'));
    }

    /**
     * @group disconnected
     */
    public function testDiscardFanOutReturnsMaxAndClearsRegistry(): void
    {
        $this->options->himport->getRegistry()->set('shared', ['name']);

        [$node1, $node2] = $this->twoNodes();
        $node1->method('executeCommand')->willReturn(1);
        $node2->method('executeCommand')->willReturn(0);

        $this->client->method('getConnection')->willReturn($this->clusterOf($node1, $node2));

        $this->assertSame(1, $this->createContainer()->discard('shared'));
        $this->assertFalse($this->options->himport->getRegistry()->has('shared'));
    }

    /**
     * @group disconnected
     */
    public function testDiscardAllFanOutReturnsMaxAndClearsRegistry(): void
    {
        $this->options->himport->getRegistry()->set('fs1', ['a']);
        $this->options->himport->getRegistry()->set('fs2', ['b']);

        [$node1, $node2] = $this->twoNodes();
        $node1->method('executeCommand')->willReturn(2);
        $node2->method('executeCommand')->willReturn(2);

        $this->client->method('getConnection')->willReturn($this->clusterOf($node1, $node2));

        $this->assertSame(2, $this->createContainer()->discardAll());
        $this->assertEmpty($this->options->himport->getRegistry()->all());
    }

    private function createContainer(): HIMPORT
    {
        return new HIMPORT($this->client);
    }

    /**
     * @return array{0: MockObject&NodeConnectionInterface, 1: MockObject&NodeConnectionInterface}
     */
    private function twoNodes(): array
    {
        return [
            $this->getMockBuilder(NodeConnectionInterface::class)->getMock(),
            $this->getMockBuilder(NodeConnectionInterface::class)->getMock(),
        ];
    }

    private function clusterOf(NodeConnectionInterface $node1, NodeConnectionInterface $node2): RedisCluster
    {
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $cluster->method('getIterator')->willReturn(new ArrayIterator([$node1, $node2]));

        return $cluster;
    }

    /**
     * @param  array            $arguments
     * @return CommandInterface
     */
    private function createCommandStub(array $arguments): CommandInterface
    {
        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        $command->method('getId')->willReturn('HIMPORT');
        $command->method('getArguments')->willReturn($arguments);

        return $command;
    }
}
