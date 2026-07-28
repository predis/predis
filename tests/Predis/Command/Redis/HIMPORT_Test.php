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

namespace Predis\Command\Redis;

use Predis\Command\RawCommand;
use Predis\Response\ServerException;
use Predis\Response\Status;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\NoBackoff;

/**
 * @group commands
 * @group realm-hash
 */
class HIMPORT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return HIMPORT::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HIMPORT';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
        $this->assertEquals(new Status('OK'), $this->getCommand()->parseResponse(new Status('OK')));
    }

    /**
     * @group disconnected
     * @dataProvider prefixKeysProvider
     */
    public function testPrefixKeys(array $arguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($arguments);
        $command->prefixKeys('prefix:');

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testPrepareAndSetCreateHashFromSharedFieldset(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->himport->prepare('shared', ['name', 'email', 'age']));
        $this->assertEquals('OK', $redis->himport->set('shared:1', 'shared', ['alice', 'alice@example.com', '25']));

        $this->assertSame('alice', $redis->hget('shared:1', 'name'));

        // Hash enumeration order is not guaranteed to match the PREPARE order
        // (the server keeps a canonical internal order), so compare sorted.
        $hash = $redis->hgetall('shared:1');
        ksort($hash);
        $this->assertSame(['age' => '25', 'email' => 'alice@example.com', 'name' => 'alice'], $hash);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testValuesMapByEachFieldsetsOwnOrder(): void
    {
        $redis = $this->getClient();

        $redis->himport->prepare('order1', ['a', 'b', 'c']);
        $redis->himport->prepare('order2', ['c', 'b', 'a']);

        $redis->himport->set('order:key1', 'order1', ['va1', 'vb1', 'vc1']);
        $redis->himport->set('order:key2', 'order2', ['vc2', 'vb2', 'va2']);

        $this->assertSame('va1', $redis->hget('order:key1', 'a'));
        $this->assertSame('va2', $redis->hget('order:key2', 'a'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testSetFullyReplacesExistingHash(): void
    {
        $redis = $this->getClient();

        $redis->hset('replace:1', 'stale', 'value');
        $redis->himport->prepare('shared', ['name', 'age']);
        $redis->himport->set('replace:1', 'shared', ['bob', '30']);

        // Full replace: the pre-existing "stale" field must be gone.
        $hash = $redis->hgetall('replace:1');
        ksort($hash);
        $this->assertSame(['age' => '30', 'name' => 'bob'], $hash);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testDiscardReturnsRemovalCount(): void
    {
        $redis = $this->getClient();

        $redis->himport->prepare('shared', ['name']);

        $this->assertSame(1, $redis->himport->discard('shared'));
        $this->assertSame(0, $redis->himport->discard('shared'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testDiscardAllReturnsRemovalCount(): void
    {
        $redis = $this->getClient();

        $redis->himport->prepare('fs1', ['a']);
        $redis->himport->prepare('fs2', ['b']);

        $this->assertSame(2, $redis->himport->discardAll());
        $this->assertSame(0, $redis->himport->discardAll());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testPrepareAndSetResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals('OK', $redis->himport->prepare('shared', ['name', 'age']));
        $this->assertEquals('OK', $redis->himport->set('shared:1', 'shared', ['carol', '40']));

        $this->assertSame('carol', $redis->hget('shared:1', 'name'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testThrowsExceptionOnDuplicateFieldName(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('duplicate field name');

        $redis = $this->getClient();
        $redis->himport->prepare('shared', ['name', 'name']);
    }

    /**
     * A PREPARE the server rejects must not leave a registry entry: a later SET
     * reports the fieldset as unknown rather than re-raising the prepare error.
     *
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testRejectedPrepareDoesNotPoisonRegistry(): void
    {
        $redis = $this->getClient();

        try {
            $redis->himport->prepare('bad', ['name', 'name']);
            $this->fail('Expected a "duplicate field name" error');
        } catch (ServerException $exception) {
            $this->assertStringContainsString('duplicate field name', $exception->getMessage());
        }

        try {
            $redis->himport->set('bad:1', 'bad', ['alice']);
            $this->fail('Expected a "no such fieldset" error');
        } catch (ServerException $exception) {
            $this->assertStringContainsString('no such fieldset', $exception->getMessage());
            $this->assertStringNotContainsString('duplicate field name', $exception->getMessage());
        }
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testThrowsExceptionOnUnknownFieldset(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('no such fieldset');

        // Fieldset never declared through the container: nothing to recover
        // from, so the server error is propagated unchanged.
        $redis = $this->getClient();
        $redis->himport->set('shared:1', 'missing', ['value']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('himport:string', 'a');
        $redis->himport->prepare('shared', ['name']);
        $redis->himport->set('himport:string', 'shared', ['value']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testRawCommandFormIsSupported(): void
    {
        $redis = $this->getClient();

        // The lower-level raw form (no client-side registry or auto-recovery)
        // remains available for pipelines and explicit connection control.
        $this->assertEquals('OK', $redis->himport('PREPARE', 'shared', 'name', 'age'));
        $this->assertEquals('OK', $redis->himport('SET', 'shared:1', 'shared', 'dave', '50'));
        $this->assertSame('dave', $redis->hget('shared:1', 'name'));
        $this->assertSame(1, $redis->himport('DISCARD', 'shared'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testMultiplePreparesAndDiscardsKeepRegistryAndSessionCommandsCoherent(): void
    {
        $redis = $this->getClient();
        $registry = $redis->getOptions()->himport->getRegistry();
        $connection = $redis->getConnection();

        $redis->himport->prepare('fs1', ['a']);
        $redis->himport->prepare('fs2', ['b', 'c']);
        $redis->himport->prepare('fs3', ['d']);

        // Both the client-side registry and the connection's replay queue track
        // every live fieldset, keyed and insertion-ordered.
        $this->assertSame(['fs1', 'fs2', 'fs3'], array_keys($registry->all()));
        $this->assertSame(
            ['himport:fs1', 'himport:fs2', 'himport:fs3'],
            array_keys($connection->getSessionCommands())
        );

        // A single discard removes exactly one fieldset from both stores.
        $this->assertSame(1, $redis->himport->discard('fs2'));
        $this->assertSame(['fs1', 'fs3'], array_keys($registry->all()));
        $this->assertSame(
            ['himport:fs1', 'himport:fs3'],
            array_keys($connection->getSessionCommands())
        );

        // Re-preparing an existing name replaces its field list (last wins).
        $redis->himport->prepare('fs1', ['a', 'x']);
        $this->assertSame(['a', 'x'], $registry->get('fs1'));

        // DISCARDALL clears both stores and reports the removal count.
        $this->assertSame(2, $redis->himport->discardAll());
        $this->assertSame([], $registry->all());
        $this->assertSame([], $connection->getSessionCommands());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReconnectTransparentlyReplaysPreparedFieldsets(): void
    {
        $redis = $this->getClient();

        $redis->himport->prepare('shared', ['name', 'age']);

        // Simulate a dropped connection mid-ingestion. The pinned PREPARE is
        // replayed automatically when the next command reconnects, so the SET
        // succeeds with no error round trip.
        $redis->getConnection()->disconnect();

        $this->assertEquals('OK', $redis->himport->set('shared:1', 'shared', ['erin', '28']));
        $this->assertSame('erin', $redis->hget('shared:1', 'name'));
        $this->assertSame('28', $redis->hget('shared:1', 'age'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReactiveRecoveryRePreparesWhenReplayIsUnavailable(): void
    {
        // Recovery reuses the connection's Retry policy, so it must be enabled.
        $redis = $this->createClient(['retry' => new Retry(new NoBackoff(), 1)]);

        $redis->himport->prepare('shared', ['name', 'age']);

        // Drop the proactive replay entry to emulate a connection that never
        // received the PREPARE (e.g. a new cluster node reached via redirection).
        $redis->getConnection()->removeSessionCommandsByPrefix('himport:');
        $redis->getConnection()->disconnect();

        // The first SET hits "no such fieldset"; the container re-prepares from
        // the registry on the executing connection and retries exactly once.
        $this->assertEquals('OK', $redis->himport->set('shared:1', 'shared', ['frank', '33']));
        $this->assertSame('frank', $redis->hget('shared:1', 'name'));
        $this->assertSame('33', $redis->hget('shared:1', 'age'));

        // Recovery re-pins the command for subsequent reconnects.
        $this->assertArrayHasKey('himport:shared', $redis->getConnection()->getSessionCommands());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testAutoPrepareDisabledPropagatesErrorWithoutRecovering(): void
    {
        $redis = $this->createClient(null, ['himport' => ['auto_prepare' => false]]);

        $redis->himport->prepare('shared', ['name', 'age']);
        $redis->getConnection()->removeSessionCommandsByPrefix('himport:');
        $redis->getConnection()->disconnect();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('no such fieldset');

        $redis->himport->set('shared:1', 'shared', ['grace', '41']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testPipelineRunsPrepareAndSetsOnASingleConnection(): void
    {
        $redis = $this->getClient();

        // The intended high-throughput pattern: PREPARE followed by dependent
        // SETs, back-to-back on one connection, using the raw command form.
        $responses = $redis->pipeline(function ($pipe) {
            $pipe->himport('PREPARE', 'shared', 'name', 'age');
            $pipe->himport('SET', 'shared:1', 'shared', 'heidi', '22');
            $pipe->himport('SET', 'shared:2', 'shared', 'ivan', '44');
        });

        $this->assertEquals('OK', $responses[0]);
        $this->assertEquals('OK', $responses[1]);
        $this->assertEquals('OK', $responses[2]);
        $this->assertSame('heidi', $redis->hget('shared:1', 'name'));
        $this->assertSame('ivan', $redis->hget('shared:2', 'name'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testTransactionRunsPrepareAndSetOnASingleConnection(): void
    {
        $redis = $this->getClient();

        $responses = $redis->transaction(function ($tx) {
            $tx->himport('PREPARE', 'shared', 'name', 'age');
            $tx->himport('SET', 'shared:1', 'shared', 'judy', '37');
        });

        $this->assertEquals('OK', $responses[0]);
        $this->assertEquals('OK', $responses[1]);
        $hash = $redis->hgetall('shared:1');
        ksort($hash);
        $this->assertSame(['age' => '37', 'name' => 'judy'], $hash);
    }

    /**
     * Full lifecycle: PREPARE enables SET, DISCARD ends it, and a SET issued
     * afterwards raises the server error (the registry is cleared by DISCARD, so
     * auto-recovery does not mask it). Keys already written survive the discard.
     *
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testFieldsetLifecycleFromPrepareThroughDiscard(): void
    {
        $redis = $this->getClient();

        $redis->himport->prepare('shared', ['name', 'age']);
        $this->assertEquals('OK', $redis->himport->set('shared:1', 'shared', ['alice', '25']));
        $this->assertEquals('OK', $redis->himport->set('shared:2', 'shared', ['bob', '30']));
        $this->assertSame('alice', $redis->hget('shared:1', 'name'));

        $this->assertSame(1, $redis->himport->discard('shared'));

        // Keys created through the fieldset are ordinary hashes and are not
        // affected by discarding it.
        $this->assertSame('bob', $redis->hget('shared:2', 'name'));

        // From here on, SET against the discarded fieldset must error.
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('no such fieldset');
        $redis->himport->set('shared:3', 'shared', ['carol', '40']);
    }

    /**
     * DISCARDALL ends every fieldset at once; each subsequent SET errors.
     *
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testDiscardAllStopsEverySubsequentSet(): void
    {
        $redis = $this->getClient();

        $redis->himport->prepare('fs1', ['a']);
        $redis->himport->prepare('fs2', ['b']);
        $this->assertEquals('OK', $redis->himport->set('k1', 'fs1', ['1']));
        $this->assertEquals('OK', $redis->himport->set('k2', 'fs2', ['2']));

        $this->assertSame(2, $redis->himport->discardAll());

        // Previously written keys survive.
        $this->assertSame('1', $redis->hget('k1', 'a'));

        foreach (['fs1', 'fs2'] as $fieldset) {
            try {
                $redis->himport->set('k_' . $fieldset, $fieldset, ['9']);
                $this->fail("Expected 'no such fieldset' for {$fieldset}");
            } catch (ServerException $exception) {
                $this->assertStringContainsString('no such fieldset', $exception->getMessage());
            }
        }
    }

    /**
     * A fieldset already present on the connection but never declared through the
     * container (prepared out of band with the raw command, or inherited on a
     * persistent connection) is usable by the container's set(): the write only
     * needs the fieldset on the executing connection, not a registry entry.
     *
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testContainerSetUsesFieldsetPreparedOutOfBand(): void
    {
        $redis = $this->getClient();

        // Prepared with the raw form: the server-side fieldset exists on the
        // connection, but the client-side registry is left untouched.
        $this->assertEquals('OK', $redis->himport('PREPARE', 'shared', 'name', 'age'));
        $this->assertFalse($redis->getOptions()->himport->getRegistry()->has('shared'));

        $this->assertEquals('OK', $redis->himport->set('shared:1', 'shared', ['alice', '25']));
        $this->assertSame('alice', $redis->hget('shared:1', 'name'));

        // Still not tracked: the container used the pre-loaded state as-is.
        $this->assertFalse($redis->getOptions()->himport->getRegistry()->has('shared'));
    }

    /**
     * Recovery is scoped to fieldsets declared through the container. A pre-loaded
     * fieldset the registry does not know about is NOT re-prepared after its
     * connection is lost, so the server error surfaces unchanged.
     *
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testPreloadedFieldsetIsNotRecoveredAfterReconnect(): void
    {
        $redis = $this->getClient();

        $redis->himport('PREPARE', 'shared', 'name', 'age');
        $this->assertEquals('OK', $redis->himport->set('shared:1', 'shared', ['alice', '25']));

        // The raw PREPARE pinned nothing for replay and recorded nothing in the
        // registry, so after a reconnect the fieldset is simply gone.
        $redis->getConnection()->disconnect();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('no such fieldset');
        $redis->himport->set('shared:2', 'shared', ['bob', '30']);
    }

    /**
     * A fieldset declared through the `himport` client option is prepared on
     * demand: a SET referencing it succeeds without an explicit prepare() call.
     *
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testConfiguredFieldsetIsPreparedOnDemandWithoutExplicitPrepare(): void
    {
        // On-demand preparation reuses the connection's Retry policy.
        $redis = $this->createClient(['retry' => new Retry(new NoBackoff(), 1)], [
            'himport' => ['fieldsets' => ['users' => ['name', 'age']]],
        ]);

        // No prepare() call - the fieldset is known from configuration.
        $this->assertEquals('OK', $redis->himport->set('users:1', 'users', ['alice', '25']));
        $this->assertEquals('OK', $redis->himport->set('users:2', 'users', ['bob', '30']));

        $this->assertSame('alice', $redis->hget('users:1', 'name'));
        $this->assertSame('bob', $redis->hget('users:2', 'name'));
    }

    /**
     * On-demand preparation of configured fieldsets uses the auto-prepare
     * mechanism; with it disabled and no explicit prepare(), the server error
     * is propagated.
     *
     * @group connected
     * @requiresRedisVersion >= 8.9.0
     */
    public function testConfiguredFieldsetWithAutoPrepareDisabledIsNotPrepared(): void
    {
        $redis = $this->createClient(null, [
            'himport' => ['fieldsets' => ['users' => ['name', 'age']], 'auto_prepare' => false],
        ]);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('no such fieldset');
        $redis->himport->set('users:1', 'users', ['alice', '25']);
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.9.0
     */
    public function testClusterFansOutPrepareToEveryMasterAndPinsPerNode(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->himport->prepare('shared', ['name', 'email', 'age']));

        // PREPARE is executed on every master shard and pinned on each node so
        // it survives that node's future reconnects.
        $masters = iterator_to_array($redis->getConnection()->getIterator());
        $this->assertGreaterThan(1, count($masters));

        foreach ($masters as $master) {
            $this->assertArrayHasKey('himport:shared', $master->getSessionCommands());
        }
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.9.0
     */
    public function testClusterRoutesSetByKeySlotAcrossShards(): void
    {
        $redis = $this->getClient();

        $redis->himport->prepare('shared', ['name', 'age']);

        // Keys chosen to land on different shards; each master already holds the
        // fanned-out fieldset, so every SET succeeds by normal slot routing.
        foreach (['shared:{a}', 'shared:{b}', 'shared:{c}', 'shared:{d}'] as $index => $key) {
            $this->assertEquals('OK', $redis->himport->set($key, 'shared', ['user' . $index, (string) (20 + $index)]));
            $this->assertSame('user' . $index, $redis->hget($key, 'name'));
        }
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.9.0
     */
    public function testClusterMultiplePreparesAndDiscardManageStatePerNode(): void
    {
        $redis = $this->getClient();

        $redis->himport->prepare('fs1', ['a']);
        $redis->himport->prepare('fs2', ['b']);

        $registry = $redis->getOptions()->himport->getRegistry();
        $this->assertSame(['fs1', 'fs2'], array_keys($registry->all()));

        foreach (iterator_to_array($redis->getConnection()->getIterator()) as $master) {
            $this->assertSame(
                ['himport:fs1', 'himport:fs2'],
                array_keys($master->getSessionCommands())
            );
        }

        // Discarding fs1 fans out to every master and updates every store.
        $this->assertSame(1, $redis->himport->discard('fs1'));
        $this->assertSame(['fs2'], array_keys($registry->all()));

        foreach (iterator_to_array($redis->getConnection()->getIterator()) as $master) {
            $this->assertSame(['himport:fs2'], array_keys($master->getSessionCommands()));
        }

        $this->assertSame(1, $redis->himport->discardAll());
        $this->assertSame([], $registry->all());

        foreach (iterator_to_array($redis->getConnection()->getIterator()) as $master) {
            $this->assertSame([], $master->getSessionCommands());
        }
    }

    /**
     * Full cluster lifecycle across shards: after fan-out PREPARE, SET succeeds
     * for keys on every shard; after fan-out DISCARD, SET errors on every shard,
     * while hashes already written survive.
     *
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.9.0
     */
    public function testClusterFieldsetLifecycleAcrossShards(): void
    {
        $redis = $this->getClient();
        $redis->himport->prepare('shared', ['name', 'age']);

        $keys = ['shared:{a}', 'shared:{b}', 'shared:{c}', 'shared:{d}'];

        foreach ($keys as $i => $key) {
            $this->assertEquals('OK', $redis->himport->set($key, 'shared', ['user' . $i, (string) (20 + $i)]));
        }
        foreach ($keys as $i => $key) {
            $this->assertSame('user' . $i, $redis->hget($key, 'name'));
        }

        // DISCARD fans out to every master; the fieldset is gone cluster-wide.
        $this->assertSame(1, $redis->himport->discard('shared'));

        // Keys written earlier are unaffected.
        $this->assertSame('user0', $redis->hget($keys[0], 'name'));

        // A SET on any shard now errors and is not auto-recovered (registry cleared).
        foreach ($keys as $key) {
            try {
                $redis->himport->set($key, 'shared', ['x', '1']);
                $this->fail("Expected 'no such fieldset' for {$key}");
            } catch (ServerException $exception) {
                $this->assertStringContainsString('no such fieldset', $exception->getMessage());
            }
        }
    }

    /**
     * Per-node state independence: with auto-prepare OFF, removing the fieldset
     * from ONE master makes SETs routed to that master fail, while SETs routed to
     * other masters keep working. Proves the error appears exactly where the
     * server-side state is missing and nowhere else.
     *
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.9.0
     */
    public function testClusterPerNodeStateWithoutAutoPrepareErrorsOnlyWhereMissing(): void
    {
        $redis = $this->createClient(null, ['himport' => ['auto_prepare' => false]]);
        $redis->himport->prepare('shared', ['name']);

        $cluster = $redis->getConnection();
        $byNode = $this->groupKeysByNode($redis, $cluster);

        if (count($byNode) < 2) {
            $this->markTestSkipped('Requires at least two distinct master shards.');
        }

        $ids = array_keys($byNode);
        $victim = $byNode[$ids[0]];
        $other = $byNode[$ids[1]];

        // Remove the fieldset from the victim master only (direct raw command).
        $victim['node']->executeCommand(RawCommand::create('HIMPORT', 'DISCARD', 'shared'));

        // SET routed to the victim shard errors; no recovery because it is off.
        try {
            $redis->himport->set($victim['keys'][0], 'shared', ['alice']);
            $this->fail('Expected a "no such fieldset" error on the victim shard');
        } catch (ServerException $exception) {
            $this->assertStringContainsString('no such fieldset', $exception->getMessage());
        }

        // SET routed to a shard that still has the fieldset keeps working.
        $this->assertEquals('OK', $redis->himport->set($other['keys'][0], 'shared', ['bob']));
        $this->assertSame('bob', $redis->hget($other['keys'][0], 'name'));
    }

    /**
     * With auto-prepare ON, a SET routed to a master that is missing the fieldset
     * (e.g. a node recycled or reached via redirection) transparently re-prepares
     * on that master and succeeds - no error surfaces where we do not want one.
     *
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.9.0
     */
    public function testClusterAutoPrepareRecoversOnNodeMissingFieldset(): void
    {
        // Recovery reuses the connection's Retry policy, so enable it.
        $redis = $this->createClient(null, ['parameters' => ['retry' => new Retry(new NoBackoff(), 1)]]);
        $redis->himport->prepare('shared', ['name']);

        $cluster = $redis->getConnection();
        $key = 'shared:{a}';
        $node = $cluster->getConnectionByCommand(
            $redis->createCommand('himport', ['SET', $key, 'shared', 'placeholder'])
        );

        // Wipe the fieldset from just the node that owns this key's slot.
        $node->executeCommand(RawCommand::create('HIMPORT', 'DISCARD', 'shared'));

        // The SET hits "no such fieldset", re-prepares on that node, retries once.
        $this->assertEquals('OK', $redis->himport->set($key, 'shared', ['carol']));
        $this->assertSame('carol', $redis->hget($key, 'name'));
    }

    /**
     * On a cluster, a fieldset pre-loaded directly on the master that owns a key
     * (out of band, without the container fan-out or a registry entry) is usable
     * by the container's set() for that key.
     *
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.9.0
     */
    public function testClusterContainerSetUsesFieldsetPreloadedOnOwningNode(): void
    {
        $redis = $this->getClient();
        $cluster = $redis->getConnection();

        $key = 'shared:{a}';
        $node = $cluster->getConnectionByCommand(
            $redis->createCommand('himport', ['SET', $key, 'shared', 'placeholder'])
        );

        // Load the fieldset only on the node that owns this key's slot; the
        // registry stays empty and no fan-out happens.
        $node->executeCommand(RawCommand::create('HIMPORT', 'PREPARE', 'shared', 'name', 'age'));
        $this->assertFalse($redis->getOptions()->himport->getRegistry()->has('shared'));

        $this->assertEquals('OK', $redis->himport->set($key, 'shared', ['dave', '44']));
        $this->assertSame('dave', $redis->hget($key, 'name'));
    }

    /**
     * A fieldset declared through the `himport` option is prepared on demand on
     * whichever master owns each key - SETs across shards succeed with no
     * explicit prepare() and no manual fan-out.
     *
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 8.9.0
     */
    public function testClusterConfiguredFieldsetPreparedOnDemandAcrossShards(): void
    {
        // On-demand preparation reuses the connection's Retry policy.
        $redis = $this->createClient(null, [
            'himport' => ['fieldsets' => ['users' => ['name', 'age']]],
            'parameters' => ['retry' => new Retry(new NoBackoff(), 1)],
        ]);

        foreach (['users:{a}', 'users:{b}', 'users:{c}', 'users:{d}'] as $i => $key) {
            $this->assertEquals('OK', $redis->himport->set($key, 'users', ['user' . $i, (string) (20 + $i)]));
        }
        foreach (['users:{a}', 'users:{b}', 'users:{c}', 'users:{d}'] as $i => $key) {
            $this->assertSame('user' . $i, $redis->hget($key, 'name'));
        }
    }

    /**
     * Groups a spread of hash-tagged keys by the master shard that owns them.
     *
     * @param  \Predis\Client                                    $redis
     * @param  mixed                                             $cluster Aggregate cluster connection.
     * @return array<string, array{node: mixed, keys: string[]}>
     */
    private function groupKeysByNode($redis, $cluster): array
    {
        $byNode = [];

        foreach (range('a', 'p') as $tag) {
            $key = 'shared:{' . $tag . '}';
            $node = $cluster->getConnectionByCommand(
                $redis->createCommand('himport', ['SET', $key, 'shared', 'placeholder'])
            );
            $id = (string) $node;

            if (!isset($byNode[$id])) {
                $byNode[$id] = ['node' => $node, 'keys' => []];
            }

            $byNode[$id]['keys'][] = $key;
        }

        return $byNode;
    }

    public function argumentsProvider(): array
    {
        return [
            'PREPARE with variadic fields' => [
                ['PREPARE', 'shared', 'name', 'email', 'age'],
                ['PREPARE', 'shared', 'name', 'email', 'age'],
            ],
            'PREPARE with fields as array' => [
                ['PREPARE', 'shared', ['name', 'email', 'age']],
                ['PREPARE', 'shared', 'name', 'email', 'age'],
            ],
            'SET with values as array' => [
                ['SET', 'shared:1', 'shared', ['alice', 'alice@example.com', '25']],
                ['SET', 'shared:1', 'shared', 'alice', 'alice@example.com', '25'],
            ],
            'field order preserved verbatim' => [
                ['PREPARE', 'order', ['c', 'a', 'b']],
                ['PREPARE', 'order', 'c', 'a', 'b'],
            ],
            'empty string fieldset and field names preserved' => [
                ['PREPARE', '', ['']],
                ['PREPARE', '', ''],
            ],
            'DISCARD' => [
                ['DISCARD', 'shared'],
                ['DISCARD', 'shared'],
            ],
            'DISCARDALL' => [
                ['DISCARDALL'],
                ['DISCARDALL'],
            ],
            'minimal placeholder shape tolerated' => [
                ['key'],
                ['key'],
            ],
        ];
    }

    public function prefixKeysProvider(): array
    {
        return [
            'SET prefixes the key at index 1 only' => [
                ['SET', 'shared:1', 'shared', 'alice', '25'],
                ['SET', 'prefix:shared:1', 'shared', 'alice', '25'],
            ],
            'PREPARE fieldset name is not prefixed' => [
                ['PREPARE', 'shared', 'name', 'age'],
                ['PREPARE', 'shared', 'name', 'age'],
            ],
            'DISCARD fieldset name is not prefixed' => [
                ['DISCARD', 'shared'],
                ['DISCARD', 'shared'],
            ],
            'DISCARDALL is untouched' => [
                ['DISCARDALL'],
                ['DISCARDALL'],
            ],
        ];
    }
}
