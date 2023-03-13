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

use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-scripting
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
     * @dataProvider flushArgumentsProvider
     */
    public function testFlushFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     * @dataProvider listArgumentsProvider
     */
    public function testListFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     * @dataProvider restoreArgumentsProvider
     */
    public function testRestoreFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
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
    public function testDumpFunctionReturnsSerializedPayload(): void
    {
        $redis = $this->getClient();

        $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $actualResponse = $redis->function->dump();

        $this->assertNotEmpty($actualResponse);
        $this->assertEquals('OK', $redis->function->delete($this->libName));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testFlushFunctionDeleteAllLibraries(): void
    {
        $redis = $this->getClient();

        $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $this->assertEquals('OK', $redis->function->flush());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testListFunctionReturnsSerializedPayload(): void
    {
        $redis = $this->getClient();

        $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $actualResponse = $redis->function->list();

        foreach (['library_name', 'engine', 'functions'] as $key) {
            $this->assertContains($key, $actualResponse[0]);
        }

        $this->assertEquals('OK', $redis->function->delete($this->libName));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testRestoreFunctionRestoresGivenSerializedFunction(): void
    {
        $redis = $this->getClient();

        $redis->function->load(
            "#!lua name={$this->libName} \n redis.register_function('myfunc', function(keys, args) return args[1] end)"
        );

        $actualResponse = $redis->function->dump();

        $redis->function->flush();

        $this->assertEquals('OK', $redis->function->restore($actualResponse));
        $this->assertEquals('OK', $redis->function->delete($this->libName));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testDeleteFunctionThrowsErrorOnNonExistingLibrary(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR Library not found');

        $redis->function->delete($this->libName);
    }

    public function flushArgumentsProvider(): array
    {
        return [
            'with ASYNC modifier' => [
                ['FLUSH', 'ASYNC'],
                ['FLUSH', 'ASYNC'],
            ],
            'with SYNC modifier' => [
                ['FLUSH', 'SYNC'],
                ['FLUSH', 'SYNC'],
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
            'with LIBRARYNAME modifier' => [
                ['LIST', 'libName'],
                ['LIST', 'LIBRARYNAME', 'libName'],
            ],
            'with WITHCODE modifier' => [
                ['LIST', '', true],
                ['LIST', 'WITHCODE'],
            ],
            'with all arguments' => [
                ['LIST', 'libName', true],
                ['LIST', 'LIBRARYNAME', 'libName', 'WITHCODE'],
            ],
        ];
    }

    public function restoreArgumentsProvider(): array
    {
        return [
            'with FLUSH modifier' => [
                ['RESTORE', 'arg2', 'FLUSH'],
                ['RESTORE', 'arg2', 'FLUSH'],
            ],
            'with APPEND modifier' => [
                ['RESTORE', 'arg2', 'APPEND'],
                ['RESTORE', 'arg2', 'APPEND'],
            ],
            'with REPLACE modifier' => [
                ['RESTORE', 'arg2', 'REPLACE'],
                ['RESTORE', 'arg2', 'REPLACE'],
            ],
        ];
    }
}
