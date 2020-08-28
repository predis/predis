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
class EXPIREAT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\EXPIREAT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'EXPIREAT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 'ttl');
        $expected = array('key', 'ttl');

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
     */
    public function testReturnsZeroOnNonExistingKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->expireat('foo', 2));
    }

    /**
     * @medium
     * @group connected
     * @group slow
     */
    public function testCanExpireKeys(): void
    {
        $redis = $this->getClient();

        $now = time();
        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertSame(1, $redis->expireat('foo', $now + 1));
        $this->assertThat($redis->ttl('foo'), $this->logicalAnd(
            $this->greaterThanOrEqual(0), $this->lessThanOrEqual(1)
        ));

        $this->sleep(2.0);
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testDeletesKeysOnPastUnixTime(): void
    {
        $redis = $this->getClient();

        $now = time();
        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertSame(1, $redis->expireat('foo', $now - 100));
        $this->assertSame(0, $redis->exists('foo'));
    }
}
