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
class RENAME_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\RENAME';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'RENAME';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 'newkey');
        $expected = array('key', 'newkey');

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
    public function testRenamesKeys(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertEquals('OK', $redis->rename('foo', 'foofoo'));
        $this->assertSame(0, $redis->exists('foo'));
        $this->assertSame(1, $redis->exists('foofoo'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnNonExistingKeys(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR no such key');

        $redis = $this->getClient();

        $redis->rename('foo', 'foobar');
    }
}
