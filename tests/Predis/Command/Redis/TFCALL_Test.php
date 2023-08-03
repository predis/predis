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

use Predis\Command\RawCommand;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Response\ServerException;

class TFCALL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TFCALL::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TFCALL';
    }

    /**
     * @dataProvider argumentsProvider
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testFilterArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedResponse, $command->getArguments());
    }

    /**
     * @group connected
     * @group gears
     * @requiresRedisGearsVersion >= 2.0.0
     * @return void
     */
    public function testCallLoadedFunctionFromRedisGearsLibrary(): void
    {
        $redis = $this->getClient();
        $libCode = "#!js api_version=1.0 name=lib\n redis.registerFunction('foo', ()=>{return 'bar'})";

        $this->assertEquals('OK', $redis->tfunction->load($libCode));
        $this->assertEquals('bar', $redis->tfcall('lib', 'foo'));
        $this->assertEquals('OK', $redis->tfunction->delete('lib'));
    }

    /**
     * @group connected
     * @group cluster
     * @group gears-cluster
     * @requiresRedisGearsVersion >= 2.0.0
     * @requiresRedisVersion >= 7.1.0
     * @return void
     */
    public function testCallLoadedFunctionFromRedisGearsLibraryClusterMode(): void
    {
        $redis = $this->getClient();

        /** @var ClusterInterface $connection */
        $connection = $redis->getConnection();

        // https://github.com/predis/predis#redis-gears-with-cluster
        $connection->executeCommandOnEachNode(
            new RawCommand('REDISGEARS_2.REFRESHCLUSTER')
        );

        $libCode = "#!js api_version=1.0 name=lib\n redis.registerFunction('foo', ()=>{return 'bar'})";

        $this->assertEquals('OK', $redis->tfunction->load($libCode));
        $this->assertEquals('bar', $redis->tfcall('lib', 'foo'));
        $this->assertEquals('OK', $redis->tfunction->delete('lib'));
    }

    /**
     * @group connected
     * @group gears
     * @requiresRedisGearsVersion >= 2.0.0
     * @return void
     */
    public function testThrowsExceptionOnNonExistingLibrary(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Unknown library lib');

        $redis->tfcall('lib', 'foo');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['lib', 'function'],
                ['lib.function', 0],
            ],
            'with keys' => [
                ['lib', 'function', ['key1', 'key2']],
                ['lib.function', 2, 'key1', 'key2'],
            ],
            'with keys and arguments' => [
                ['lib', 'function', ['key1', 'key2'], ['arg1', 'arg2']],
                ['lib.function', 2, 'key1', 'key2', 'arg1', 'arg2'],
            ],
        ];
    }
}
