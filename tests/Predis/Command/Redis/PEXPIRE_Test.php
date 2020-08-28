<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-key
 */
class PEXPIRE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\PEXPIRE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PEXPIRE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 100);
        $expected = array('key', 100);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testReturnsZeroOnNonExistingKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->pexpire('foo', 20000));
    }

    /**
     * @medium
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     * @group slow
     */
    public function testCanExpireKeys(): void
    {
        $ttl = 1000;
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertSame(1, $redis->pexpire('foo', $ttl));

        $this->sleep(1.2);
        $this->assertSame(0, $redis->exists('foo'));
    }

     /**
      * @medium
      * @group connected
      * @requiresRedisVersion >= 2.6.0
      * @group slow
      */
     public function testConsistencyWithTTL(): void
     {
         $ttl = 1000;
         $redis = $this->getClient();

         $this->assertEquals('OK', $redis->set('foo', 'bar'));
         $this->assertSame(1, $redis->pexpire('foo', $ttl));

         $this->sleep(0.5);
         $this->assertThat($redis->pttl('foo'), $this->logicalAnd(
            $this->lessThanOrEqual($ttl), $this->greaterThan($ttl - 800)
        ));
     }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testDeletesKeysOnNegativeTTL(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertSame(1, $redis->pexpire('foo', -10000));
        $this->assertSame(0, $redis->exists('foo'));
    }
}
