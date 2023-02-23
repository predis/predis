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
 * @group relay-wtf
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
    public function testDeleteFunctionThrowsErrorOnNonExistingLibrary(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR Library not found');

        $redis->function->delete($this->libName);
    }
}
