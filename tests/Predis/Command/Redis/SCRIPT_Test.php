<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-scripting
 */
class SCRIPT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SCRIPT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SCRIPT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['EXISTS', '9d0c0826bde023cc39eebaaf832c32a890f3b088', 'ffffffffffffffffffffffffffffffffffffffff'];
        $expected = ['EXISTS', '9d0c0826bde023cc39eebaaf832c32a890f3b088', 'ffffffffffffffffffffffffffffffffffffffff'];

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
     * @requiresRedisVersion >= 2.6.0
     */
    public function testExistsReturnsAnArrayOfValues(): void
    {
        $redis = $this->getClient();

        $redis->eval($lua = 'return true', 0);
        $sha1 = sha1($lua);

        $this->assertSame([1, 0], $redis->script('EXISTS', $sha1, 'ffffffffffffffffffffffffffffffffffffffff'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testLoadReturnsHashOfScripts(): void
    {
        $redis = $this->getClient();

        $lua = 'return true';
        $sha1 = sha1($lua);

        $this->assertSame($sha1, $redis->script('LOAD', $lua));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testFlushesExistingScripts(): void
    {
        $redis = $this->getClient();

        $sha1 = $redis->script('LOAD', 'return true');

        $this->assertEquals('OK', $redis->script('FLUSH'));
        $this->assertSame([0], $redis->script('EXISTS', $sha1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnInvalidSubcommand(): void
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();

        $redis->script('INVALID');
    }
}
