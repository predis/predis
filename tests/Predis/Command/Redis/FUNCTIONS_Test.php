<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-scripting
 * @requiresRedisVersion >= 7.0.0
 */
class FUNCTIONS_Test extends PredisCommandTestCase
{
    /**
     * @var string
     */
    private $libName = 'mylib';

    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return FUNCTIONS::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'FUNCTION';
    }

    /**
     * @group disconnected
     */
    public function testLoadFilterArguments(): void
    {
        $arguments = ['LOAD', 'function-code', true];
        $expected = ['LOAD', 'function-code', 'REPLACE'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testDeleteFilterArguments(): void
    {
        $arguments = ['DELETE', 'libraryName'];
        $expected = ['DELETE', 'libraryName'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testDumpFilterArguments(): void
    {
        $arguments = ['DUMP'];
        $expected = ['DUMP'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testKillFilterArguments(): void
    {
        $arguments = ['KILL'];
        $expected = ['KILL'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testStatsFilterArguments(): void
    {
        $arguments = ['STATS'];
        $expected = ['STATS'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @dataProvider flushArgumentsProvider
     * @group disconnected
     */
    public function testFlushFilterArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedResponse, $command->getArguments());
    }

    /**
     * @dataProvider restoreArgumentsProvider
     * @group disconnected
     */
    public function testRestoreFilterArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedResponse, $command->getArguments());
    }

    /**
     * @dataProvider listArgumentsProvider
     * @group disconnected
     */
    public function testListFilterArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedResponse, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testLoadFunctionAddFunctionIntoGivenLibrary(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $actualResponse = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertSame('mylib', $actualResponse);
        $this->assertSame('arg1', $redis->fcall('myfunc', [], 'arg1'));
        $this->assertEquals('OK', $redis->function->delete($this->libName));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testLoadFunctionAddFunctionIntoGivenLibraryResp3(): void
    {
        $redis = $this->getResp3Client();

        $actualResponse = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertSame('mylib', $actualResponse);
        $this->assertSame('arg1', $redis->fcall('myfunc', [], 'arg1'));
        $this->assertEquals('OK', $redis->function->delete($this->libName));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testLoadFunctionOverridesExistingFunctionWithReplaceArgumentGiven(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $actualResponse = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertSame($this->libName, $actualResponse);
        $this->assertSame('arg1', $redis->fcall('myfunc', [], 'arg1'));

        $overriddenResponse = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[2] end)",
            true
        );

        $this->assertSame($this->libName, $overriddenResponse);
        $this->assertSame('arg2', $redis->fcall('myfunc', [], 'arg1', 'arg2'));
        $this->assertEquals('OK', $redis->function->delete($this->libName));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testLoadFunctionThrowsErrorOnAlreadyExistingLibraryGiven(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $actualResponse = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertSame($this->libName, $actualResponse);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage("ERR Library '{$this->libName}' already exists");

        try {
            $redis->function->load(
                "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
            );
        } finally {
            $this->assertEquals('OK', $redis->function->delete($this->libName));
        }
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testDeleteFunctionRemovesAlreadyExistingLibrary(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $actualResponse = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertSame($this->libName, $actualResponse);
        $this->assertEquals('OK', $redis->function->delete($this->libName));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testDumpReturnsSerializedPayloadOfLibrary(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $libName = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertSame($this->libName, $libName);
        $this->assertStringContainsString($libName, $redis->function->dump());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testFlushRemovesAllLibraries(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $libName = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertEquals($this->libName, $libName);
        $this->assertEquals('OK', $redis->function->flush());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testRestoresLibraryFromSerializedPayload(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $libName = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );
        $this->assertEquals($this->libName, $libName);

        $serializedPayload = $redis->function->dump();
        $this->assertStringContainsString($libName, $serializedPayload);

        $redis->function->flush();

        $this->assertEquals('OK', $redis->function->restore($serializedPayload));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testListReturnsListOfAvailableFunctions(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();
        $expectedResponse = [
            [
                'library_name', 'mylib', 'engine', 'LUA', 'functions',
                [
                    ['name', 'myfunc', 'description', null, 'flags', []],
                ],
            ],
        ];

        $libName = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertEquals($this->libName, $libName);
        $this->assertSame($expectedResponse, $redis->function->list());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testStatsReturnsInformationAboutRunningScript(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();
        $expectedResponse = ['running_script', null, 'engines', ['LUA', ['libraries_count', 1, 'functions_count', 1]]];

        $libName = $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertEquals($this->libName, $libName);
        $this->assertSame($expectedResponse, $redis->function->stats());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testDeleteFunctionThrowsErrorOnNonExistingLibrary(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR Library not found');

        $redis->function->delete($this->libName);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testKillThrowsExceptionOnNonExistingRunningScript(): void
    {
        $redis = $this->getClient();
        $redis->function->flush();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('NOTBUSY No scripts in execution right now.');

        $redis->function->kill();
    }

    public function flushArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['FLUSH', null],
                ['FLUSH'],
            ],
            'with mode argument' => [
                ['FLUSH', 'sync'],
                ['FLUSH', 'SYNC'],
            ],
        ];
    }

    public function restoreArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['RESTORE', 'value', null],
                ['RESTORE', 'value'],
            ],
            'with mode argument' => [
                ['RESTORE', 'value', 'append'],
                ['RESTORE', 'value', 'APPEND'],
            ],
        ];
    }

    public function listArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['LIST', null, false],
                ['LIST'],
            ],
            'with LIBRARYNAME modifier' => [
                ['LIST', 'libraryname', false],
                ['LIST', 'LIBRARYNAME', 'libraryname'],
            ],
            'with WITHCODE modifier' => [
                ['LIST', null, true],
                ['LIST', 'WITHCODE'],
            ],
            'with all arguments' => [
                ['LIST', 'libraryname', true],
                ['LIST', 'LIBRARYNAME', 'libraryname', 'WITHCODE'],
            ],
        ];
    }
}
