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
        $arguments = ['foo', 'bar', true, 'EX', '10', 'NX'];
        $expected = ['foo', 'bar', 'GET', 'EX', '10', 'NX'];

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

        $this->assertEquals('OK', $redis->set('foo', 'bar', false, 'ex', 1));
        $this->assertSame(1, $redis->ttl('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierPX(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar', false, 'px', 1000));

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

        $this->assertEquals('OK', $redis->set('foo', 'bar', false, 'NX'));
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

        $this->assertEquals('OK', $redis->set('foo', 'barbar', false, 'XX'));
        $this->assertNull($redis->set('foofoo', 'barbar', false, 'XX'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testSetOverrideStringValueAndRetainsOldTTL(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar', false, 'EX', 100);
        $this->assertGreaterThanOrEqual(99, $redis->ttl('foo'));
        $this->assertLessThanOrEqual(100, $redis->ttl('foo'));

        $this->assertEquals('OK', $redis->set('foo', 'barbar', false, 'KEEPTTL'));
        $this->assertGreaterThanOrEqual(99, $redis->ttl('foo'));
        $this->assertLessThanOrEqual(100, $redis->ttl('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testSetReturnsOldValueIfItPreviouslyExistsWithGetModifier(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame('bar', $redis->set('foo', 'foobar', true));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testSetReturnsNullIfKeyDidNotExistsWithGetModifier(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->set('foo', 'foobar', true));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testSetReturnsNullIfKeyDidNotExistsWithGetAndNXModifier(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->set('foo', 'foobar', true, 'NX'));
    }
}
