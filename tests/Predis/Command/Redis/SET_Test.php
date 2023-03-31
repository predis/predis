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
 * @group realm-string
 */
class SET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['foo', 'bar'];
        $expected = ['foo', 'bar'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsRedisWithModifiers(): void
    {
        $arguments = ['foo', 'bar', 'EX', '10', 'NX'];
        $expected = ['foo', 'bar', 'EX', '10', 'NX'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     */
    public function testSetStringValue(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame('bar', $redis->get('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierEX(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar', 'ex', 1));
        $this->assertSame(1, $redis->ttl('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierPX(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar', 'px', 1000));

        $pttl = $redis->pttl('foo');
        $this->assertGreaterThan(0, $pttl);
        $this->assertLessThanOrEqual(1000, $pttl);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierNX(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar', 'NX'));
        $this->assertNull($redis->set('foo', 'bar', 'NX'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierXX(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertEquals('OK', $redis->set('foo', 'barbar', 'XX'));
        $this->assertNull($redis->set('foofoo', 'barbar', 'XX'));
    }

    /**
     * @group connected
     * @group cluster
     * @requiresRedisVersion >= 3.0.0
     * @return void
     */
    public function testSetStringValueInClusterMode(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
    }
}
