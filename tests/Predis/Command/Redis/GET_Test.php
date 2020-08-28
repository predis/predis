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
 * @group realm-string
 */
class GET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\GET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'GET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('foo');
        $expected = array('foo');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('bar', $this->getCommand()->parseResponse('bar'));
    }

    /**
     * @group connected
     */
    public function testReturnsStringValue(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertEquals('bar', $redis->get('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsEmptyStringOnEmptyStrings(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', '');

        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame('', $redis->get('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsNullOnNonExistingKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->exists('foo'));
        $this->assertNull($redis->get('foo'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->rpush('metavars', 'foo');
        $redis->get('metavars');
    }
}
