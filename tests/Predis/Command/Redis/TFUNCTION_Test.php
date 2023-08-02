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

class TFUNCTION_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TFUNCTION::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TFUNCTION';
    }

    /**
     * @dataProvider loadArgumentsProvider
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testSetLoadArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedResponse, $command->getArguments());
    }

    /**
     * @dataProvider listArgumentsProvider
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testSetListArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedResponse, $command->getArguments());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDeleteFilterArguments(): void
    {
        $arguments = ['DELETE', 'libname'];
        $expected = ['DELETE', 'libname'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     * @dataProvider responseProvider
     * @param  array $arguments
     * @param  array $actualData
     * @param  array $expectedData
     * @return void
     */
    public function testParseResponse(array $arguments, array $actualData, array $expectedData): void
    {
        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertEquals($expectedData, $command->parseResponse($actualData));
    }

    /**
     * @group connected
     * @group gears
     * @requiresRedisGearsVersion >= 2.0.0
     * @return void
     */
    public function testLoadAndDeletesGivenLibraryFromRedisGears(): void
    {
        $redis = $this->getClient();
        $libCode = "#!js api_version=1.0 name=lib\n redis.registerFunction('foo', ()=>{return 'bar'})";

        $this->assertEquals('OK', $redis->tfunction->load($libCode));
        $this->assertEquals('OK', $redis->tfunction->delete('lib'));
    }

    /**
     * @group connected
     * @group gears-cluster
     * @requiresRedisGearsVersion >= 2.0.0
     * @return void
     */
    public function testLoadAndDeletesGivenLibraryFromRedisGearsClusterMode(): void
    {
        $redis = $this->getClient();
        $libCode = "#!js api_version=1.0 name=lib\n redis.registerFunction('foo', ()=>{return 'bar'})";

        $this->assertEquals('OK', $redis->tfunction->load($libCode));
        $this->assertEquals('OK', $redis->tfunction->delete('lib'));
    }

    /**
     * @group connected
     * @group gears
     * @requiresRedisGearsVersion >= 2.0.0
     * @return void
     */
    public function testListsRedisGearsLibraries(): void
    {
        $redis = $this->getClient();
        $libCode = "#!js api_version=1.0 name=lib\n redis.registerFunction('foo', ()=>{return 'bar'})";

        $this->assertEquals('OK', $redis->tfunction->load($libCode));
        $this->assertEquals('lib', $redis->tfunction->list()[0]['name']);
        $this->assertEquals('OK', $redis->tfunction->delete('lib'));
    }

    public function loadArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['LOAD', 'libcode'],
                ['LOAD', 'libcode'],
            ],
            'with REPLACE argument' => [
                ['LOAD', 'libcode', true],
                ['LOAD', 'REPLACE', 'libcode'],
            ],
            'with CONFIG argument' => [
                ['LOAD', 'libcode', false, 'config'],
                ['LOAD', 'CONFIG', 'config', 'libcode'],
            ],
            'with all arguments' => [
                ['LOAD', 'libcode', true, 'config'],
                ['LOAD', 'REPLACE', 'CONFIG', 'config', 'libcode'],
            ],
        ];
    }

    public function listArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['LIST'],
                ['LIST'],
            ],
            'with WITHCODE argument' => [
                ['LIST', true],
                ['LIST', 'WITHCODE'],
            ],
            'with verbose level argument' => [
                ['LIST', false, 2],
                ['LIST', 'v', 'v'],
            ],
            'with verbose level above threshold' => [
                ['LIST', false, 9999],
                ['LIST', 'v', 'v', 'v'],
            ],
            'with LIBRARY argument' => [
                ['LIST', false, 0, 'libname'],
                ['LIST', 'LIBRARY', 'libname'],
            ],
            'with all arguments' => [
                ['LIST', true, 2, 'libname'],
                ['LIST', 'WITHCODE', 'v', 'v', 'LIBRARY', 'libname'],
            ],
        ];
    }

    public function responseProvider(): array
    {
        return [
            'with non-LIST subcommand' => [
                ['LOAD', 'lib'],
                [['key', 'value']],
                [['key', 'value']],
            ],
            'with LIST subcommand' => [
                ['LIST'],
                [['key', 'value', ['key1', 'value1']]],
                [['key' => 'value', 2 => ['key1' => 'value1']]],
            ],
        ];
    }
}
