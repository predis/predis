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
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Predis\NotSupportedException;
use Predis\Response\Error;
use Predis\Response\ServerException;
use Predis\Response\Status;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\NoBackoff;

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

        $node = $this->nodeWithRetries(1);
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
    public function testSetDoesNotRetryWhenRetriesAreDisabled(): void
    {
        $this->options->himport->getRegistry()->set('shared', ['name']);

        // Retries disabled (the default): no re-prepare, no retry.
        $node = $this->nodeWithRetries(0);
        $node->expects($this->never())->method('executeCommand');
        $this->client->method('getConnection')->willReturn($node);

        $this->client
            ->expects($this->once())
            ->method('executeCommand')
            ->willThrowException(new ServerException('ERR no such fieldset'));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('no such fieldset');

        $this->createContainer()->set('shared:1', 'shared', ['alice']);
    }

    /**
     * @group disconnected
     */
    public function testSetRecoveryIsBoundedByTheConfiguredRetryCount(): void
    {
        $this->options->himport->getRegistry()->set('shared', ['name']);

        $node = $this->nodeWithRetries(1);
        $node->method('executeCommand')->willReturn(Status::get('OK'));
        $this->client->method('getConnection')->willReturn($node);

        // One configured retry => the SET is attempted exactly twice.
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

        $node = $this->nodeWithRetries(1);
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
     * A re-prepare that the server rejects must honour the exceptions option:
     * with exceptions off it returns the Error response (root cause), not throws.
     *
     * @group disconnected
     */
    public function testSetReturnsErrorWhenReprepareFailsAndExceptionsDisabled(): void
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

        // The re-PREPARE (executed directly on the node) is rejected by the server.
        $node = $this->nodeWithRetries(1);
        $node->method('executeCommand')->willReturn(new Error('ERR duplicate field name in fieldset'));
        $this->client->method('getConnection')->willReturn($node);

        // The SET itself reports "no such fieldset", triggering recovery.
        $this->client
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn(new Error('ERR no such fieldset'));

        $response = $this->createContainer()->set('shared:1', 'shared', ['alice']);

        $this->assertInstanceOf(Error::class, $response);
        $this->assertStringContainsString('duplicate field name', $response->getMessage());
    }

    /**
     * The mirror case: with exceptions on, a rejected re-prepare surfaces the
     * root cause as a thrown ServerException.
     *
     * @group disconnected
     */
    public function testSetThrowsWhenReprepareFailsAndExceptionsEnabled(): void
    {
        $this->options->himport->getRegistry()->set('shared', ['name']);

        $node = $this->nodeWithRetries(1);
        $node->method('executeCommand')->willReturn(new Error('ERR duplicate field name in fieldset'));
        $this->client->method('getConnection')->willReturn($node);

        $this->client
            ->expects($this->once())
            ->method('executeCommand')
            ->willThrowException(new ServerException('ERR no such fieldset'));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('duplicate field name');

        $this->createContainer()->set('shared:1', 'shared', ['alice']);
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
    public function testPrepareFanOutSurfacesFirstErrorAndDoesNotRecord(): void
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

        // A rejected PREPARE must not leave a poison registry entry that later
        // SETs would keep trying (and failing) to re-prepare.
        $this->assertFalse($this->options->himport->getRegistry()->has('shared'));
    }

    /**
     * @group disconnected
     */
    public function testPrepareDoesNotRecordWhenStandaloneServerRejectsIt(): void
    {
        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $this->client->method('getConnection')->willReturn($node);
        $this->client
            ->expects($this->once())
            ->method('executeCommand')
            ->willThrowException(new ServerException('ERR duplicate field name in fieldset'));

        try {
            $this->createContainer()->prepare('shared', ['name', 'name']);
            $this->fail('Expected ServerException was not thrown');
        } catch (ServerException $exception) {
            $this->assertStringContainsString('duplicate field name', $exception->getMessage());
        }

        $this->assertFalse($this->options->himport->getRegistry()->has('shared'));
    }

    /**
     * @group disconnected
     */
    public function testDiscardKeepsRegistryWhenServerRejectsIt(): void
    {
        $this->options->himport->getRegistry()->set('shared', ['name']);

        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $this->client->method('getConnection')->willReturn($node);
        $this->client
            ->method('executeCommand')
            ->willThrowException(new ServerException('ERR something went wrong'));

        try {
            $this->createContainer()->discard('shared');
            $this->fail('Expected ServerException was not thrown');
        } catch (ServerException $exception) {
            // A failed discard leaves the fieldset consistently known and retryable.
        }

        $this->assertTrue($this->options->himport->getRegistry()->has('shared'));
    }

    /**
     * A ClusterInterface that cannot be iterated must fail fast rather than look
     * like a successful fan-out over zero nodes.
     *
     * @group disconnected
     */
    public function testPrepareFailsFastWhenClusterIsNotIterable(): void
    {
        // A bare ClusterInterface mock does not implement IteratorAggregate.
        $cluster = $this->getMockBuilder(ClusterInterface::class)->getMock();
        $this->client->method('getConnection')->willReturn($cluster);

        try {
            $this->createContainer()->prepare('shared', ['name']);
            $this->fail('Expected a NotSupportedException was not thrown');
        } catch (NotSupportedException $exception) {
            $this->assertStringContainsString('iterable', $exception->getMessage());
        }

        // Nothing was executed, so nothing must have been recorded.
        $this->assertFalse($this->options->himport->getRegistry()->has('shared'));
    }

    /**
     * An iterable cluster that yields no master shards must also fail fast.
     *
     * @group disconnected
     */
    public function testPrepareFailsFastWhenClusterHasNoMasters(): void
    {
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIterator'])
            ->getMock();
        $cluster->method('getIterator')->willReturn(new ArrayIterator([]));
        $this->client->method('getConnection')->willReturn($cluster);

        $this->expectException(NotSupportedException::class);

        $this->createContainer()->prepare('shared', ['name']);
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
     * A node connection whose parameters carry a Retry configured with the given
     * number of retries (0 = disabled), so the container reuses it as-is.
     *
     * @param  int                                $retries
     * @return MockObject&NodeConnectionInterface
     */
    private function nodeWithRetries(int $retries)
    {
        $node = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $node
            ->method('getParameters')
            ->willReturn(new Parameters(['retry' => new Retry(new NoBackoff(), $retries)]));

        return $node;
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
