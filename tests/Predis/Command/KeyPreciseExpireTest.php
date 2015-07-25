<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @group commands
 * @group realm-key
 */
class KeyPreciseExpireTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\KeyPreciseExpire';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'PEXPIRE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
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
    public function testParseResponse()
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group connected
     */
    public function testReturnsZeroOnNonExistingKeys()
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->pexpire('foo', 20000));
    }

    /**
     * @medium
     * @group connected
     * @group slow
     */
    public function testCanExpireKeys()
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
      * @group slow
      */
     public function testConsistencyWithTTL()
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
     */
    public function testDeletesKeysOnNegativeTTL()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertSame(1, $redis->pexpire('foo', -10000));
        $this->assertSame(0, $redis->exists('foo'));
    }
}
