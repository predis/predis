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
class KeyRenamePreserveTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\KeyRenamePreserve';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'RENAMENX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
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
    public function testParseResponse()
    {
        $this->assertSame(0, $this->getCommand()->parseResponse(0));
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     */
    public function testRenamesKeys()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(1, $redis->renamenx('foo', 'foofoo'));
        $this->assertSame(0, $redis->exists('foo'));
        $this->assertSame(1, $redis->exists('foofoo'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR no such key
     */
    public function testThrowsExceptionWhenRenamingNonExistingKeys()
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->renamenx('foo', 'foobar'));
    }
}
