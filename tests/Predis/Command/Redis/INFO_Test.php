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

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-server
 */
class INFO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\INFO';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'INFO';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $command->setArguments(array());

        $this->assertSame(array(), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCanParseNewResponseFormat(): void
    {
        $raw = <<<BUFFER
# Server
redis_version:2.9.0
redis_git_sha1:237194b7
redis_git_dirty:0
arch_bits:32
multiplexing_api:epoll
process_id:16620
tcp_port:6379
uptime_in_seconds:444
uptime_in_days:0
lru_clock:198040

# Clients
connected_clients:1
client_longest_output_list:0
client_biggest_input_buf:0
blocked_clients:0

# Memory
used_memory:628076
used_memory_human:613.36K
used_memory_rss:1568768
used_memory_peak:570056
used_memory_peak_human:556.70K
used_memory_lua:14336
mem_fragmentation_ratio:2.50
mem_allocator:jemalloc-2.2.1

# Persistence
loading:0
aof_enabled:0
changes_since_last_save:0
bgsave_in_progress:0
last_save_time:1323185719
bgrewriteaof_in_progress:0

# Stats
total_connections_received:4
total_commands_processed:3
rejected_connections:0
expired_keys:0
evicted_keys:0
keyspace_hits:0
keyspace_misses:0
pubsub_channels:0
pubsub_patterns:0
latest_fork_usec:0

# Replication
role:master
connected_slaves:0

# CPU
used_cpu_sys:0.06
used_cpu_user:0.06
used_cpu_sys_children:0.00
used_cpu_user_children:0.00

# Cluster
cluster_enabled:0

# Keyspace
db0:keys=2,expires=0
db5:keys=1,expires=0

BUFFER;

        $expected = array(
            'Server' => array(
                'redis_version' => '2.9.0',
                'redis_git_sha1' => '237194b7',
                'redis_git_dirty' => '0',
                'arch_bits' => '32',
                'multiplexing_api' => 'epoll',
                'process_id' => '16620',
                'tcp_port' => '6379',
                'uptime_in_seconds' => '444',
                'uptime_in_days' => '0',
                'lru_clock' => '198040',
            ),
            'Clients' => array(
                'connected_clients' => '1',
                'client_longest_output_list' => '0',
                'client_biggest_input_buf' => '0',
                'blocked_clients' => '0',
            ),
            'Memory' => array(
                'used_memory' => '628076',
                'used_memory_human' => '613.36K',
                'used_memory_rss' => '1568768',
                'used_memory_peak' => '570056',
                'used_memory_peak_human' => '556.70K',
                'used_memory_lua' => '14336',
                'mem_fragmentation_ratio' => '2.50',
                'mem_allocator' => 'jemalloc-2.2.1',
            ),
            'Persistence' => array(
                'loading' => '0',
                'aof_enabled' => '0',
                'changes_since_last_save' => '0',
                'bgsave_in_progress' => '0',
                'last_save_time' => '1323185719',
                'bgrewriteaof_in_progress' => '0',
            ),
            'Stats' => array(
                'total_connections_received' => '4',
                'total_commands_processed' => '3',
                'rejected_connections' => '0',
                'expired_keys' => '0',
                'evicted_keys' => '0',
                'keyspace_hits' => '0',
                'keyspace_misses' => '0',
                'pubsub_channels' => '0',
                'pubsub_patterns' => '0',
                'latest_fork_usec' => '0',
            ),
            'Replication' => array(
                'role' => 'master',
                'connected_slaves' => '0',
            ),
            'CPU' => array(
                'used_cpu_sys' => '0.06',
                'used_cpu_user' => '0.06',
                'used_cpu_sys_children' => '0.00',
                'used_cpu_user_children' => '0.00',
            ),
            'Cluster' => array(
                'cluster_enabled' => '0',
            ),
            'Keyspace' => array(
                'db0' => array('keys' => '2', 'expires' => '0'),
                'db5' => array('keys' => '1', 'expires' => '0'),
            ),
        );

        $this->assertSame($expected, $this->getCommand()->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testCanParseOldResponsesFormat(): void
    {
        $raw = <<<BUFFER
redis_version:2.4.4
redis_git_sha1:bc62bc5e
redis_git_dirty:0
arch_bits:32
multiplexing_api:epoll
process_id:15640
uptime_in_seconds:792
uptime_in_days:0
lru_clock:197890
used_cpu_sys:0.08
used_cpu_user:0.10
used_cpu_sys_children:0.00
used_cpu_user_children:0.00
connected_clients:1
connected_slaves:0
client_longest_output_list:0
client_biggest_input_buf:0
blocked_clients:0
used_memory:556156
used_memory_human:543.12K
used_memory_rss:1396736
used_memory_peak:547688
used_memory_peak_human:534.85K
mem_fragmentation_ratio:2.51
mem_allocator:jemalloc-2.2.1
loading:0
aof_enabled:0
changes_since_last_save:0
bgsave_in_progress:0
last_save_time:1323183872
bgrewriteaof_in_progress:0
total_connections_received:2
total_commands_processed:1
expired_keys:0
evicted_keys:0
keyspace_hits:0
keyspace_misses:0
pubsub_channels:0
pubsub_patterns:0
latest_fork_usec:0
vm_enabled:0
role:master
db0:keys=2,expires=0
db5:keys=1,expires=0

BUFFER;

        $expected = array(
            'redis_version' => '2.4.4',
            'redis_git_sha1' => 'bc62bc5e',
            'redis_git_dirty' => '0',
            'arch_bits' => '32',
            'multiplexing_api' => 'epoll',
            'process_id' => '15640',
            'uptime_in_seconds' => '792',
            'uptime_in_days' => '0',
            'lru_clock' => '197890',
            'used_cpu_sys' => '0.08',
            'used_cpu_user' => '0.10',
            'used_cpu_sys_children' => '0.00',
            'used_cpu_user_children' => '0.00',
            'connected_clients' => '1',
            'connected_slaves' => '0',
            'client_longest_output_list' => '0',
            'client_biggest_input_buf' => '0',
            'blocked_clients' => '0',
            'used_memory' => '556156',
            'used_memory_human' => '543.12K',
            'used_memory_rss' => '1396736',
            'used_memory_peak' => '547688',
            'used_memory_peak_human' => '534.85K',
            'mem_fragmentation_ratio' => '2.51',
            'mem_allocator' => 'jemalloc-2.2.1',
            'loading' => '0',
            'aof_enabled' => '0',
            'changes_since_last_save' => '0',
            'bgsave_in_progress' => '0',
            'last_save_time' => '1323183872',
            'bgrewriteaof_in_progress' => '0',
            'total_connections_received' => '2',
            'total_commands_processed' => '1',
            'expired_keys' => '0',
            'evicted_keys' => '0',
            'keyspace_hits' => '0',
            'keyspace_misses' => '0',
            'pubsub_channels' => '0',
            'pubsub_patterns' => '0',
            'latest_fork_usec' => '0',
            'vm_enabled' => '0',
            'role' => 'master',
            'db0' => array('keys' => '2', 'expires' => '0'),
            'db5' => array('keys' => '1', 'expires' => '0'),
        );

        $this->assertSame($expected, $this->getCommand()->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testDoesNotEmitPhpNoticeOnEmptyResponse(): void
    {
        $this->assertSame(array(), $this->getCommand()->parseResponse(''));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testReturnsAnArrayOfInfo(): void
    {
        $redis = $this->getClient();
        $command = $this->getCommand();

        $this->assertIsArray($info = $redis->executeCommand($command));
        $this->assertArrayHasKey('redis_version', isset($info['Server']) ? $info['Server'] : $info);
    }
    /**
     * @group connected
     * @requiresRedisVersion < 2.6.0
     */
    public function testReturnsAnArrayOfInfoOnOlderRedisVersions(): void
    {
        $redis = $this->getClient();
        $command = $this->getCommand();

        $this->assertIsArray($info = $redis->executeCommand($command));
        $this->assertArrayHasKey('redis_version', $info);
    }
}
