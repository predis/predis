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

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-transaction
 */
class WATCH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\WATCH';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'WATCH';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key1', 'key2', 'key3'];
        $expected = ['key1', 'key2', 'key3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = [['key1', 'key2', 'key3']];
        $expected = ['key1', 'key2', 'key3'];

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
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 'arg2', 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testAbortsTransactionOnExternalWriteOperations(): void
    {
        $redis1 = $this->getClient();
        $redis2 = $this->getClient();

        $redis1->mset('foo', 'bar', 'hoge', 'piyo');

        $this->assertEquals('OK', $redis1->watch('foo', 'hoge'));
        $this->assertEquals('OK', $redis1->multi());
        $this->assertEquals('QUEUED', $redis1->get('foo'));
        $this->assertEquals('OK', $redis2->set('foo', 'hijacked'));
        $this->assertNull($redis1->exec());
        $this->assertSame('hijacked', $redis1->get('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testAbortsTransactionOnExternalWriteOperationsResp3(): void
    {
        $redis1 = $this->getResp3Client();
        $redis2 = $this->getResp3Client();

        $redis1->mset('foo', 'bar', 'hoge', 'piyo');

        $this->assertEquals('OK', $redis1->watch('foo', 'hoge'));
        $this->assertEquals('OK', $redis1->multi());
        $this->assertEquals('QUEUED', $redis1->get('foo'));
        $this->assertEquals('OK', $redis2->set('foo', 'hijacked'));
        $this->assertNull($redis1->exec());
        $this->assertSame('hijacked', $redis1->get('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testCanWatchNotYetExistingKeys(): void
    {
        $redis1 = $this->getClient();
        $redis2 = $this->getClient();

        $this->assertEquals('OK', $redis1->watch('foo'));
        $this->assertEquals('OK', $redis1->multi());
        $this->assertEquals('QUEUED', $redis1->set('foo', 'bar'));
        $this->assertEquals('OK', $redis2->set('foo', 'hijacked'));
        $this->assertNull($redis1->exec());
        $this->assertSame('hijacked', $redis1->get('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionWhenCallingInsideTransaction(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR WATCH inside MULTI is not allowed');

        $redis = $this->getClient();

        $redis->multi();
        $redis->watch('foo');
    }
}
