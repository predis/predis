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

use PHPUnit\Util\Test as TestUtil;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-scripting
 */
class FCALL_RO_Test extends PredisCommandTestCase
{
    private const LIB_NAME = 'mylib';

    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return FCALL_RO::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'FCALL_RO';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
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
    public function testInvokeGivenReadOnlyFunction(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('key', 'value'));

        $this->assertSame(
            self::LIB_NAME,
            $redis->function->load(
                "#!lua name=mylib\n redis.register_function{function_name='myfunc',callback=function(keys, args) return redis.call('GET', keys[1]) end,flags={'no-writes'}}"
            )
        );

        $actualResponse = $redis->fcall_ro('myfunc', ['key']);
        $this->assertSame('value', $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsExceptionOnWriteContextFunction(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('key', 'value'));

        $this->assertSame(
            self::LIB_NAME,
            $redis->function->load(
                "#!lua name=mylib \n redis.register_function('myfunc',function(keys, args) return redis.call('GET', keys[1]) end)"
            )
        );

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR Can not execute a script with write flag using *_ro command.');

        $redis->fcall_ro('myfunc', ['key']);
    }

    protected function tearDown(): void
    {
        $annotations = TestUtil::parseTestMethodAnnotations(
            get_class($this),
            $this->getName(false)
        );

        if (
            isset($annotations['method']['group'])
            && in_array('connected', $annotations['method']['group'], true)
        ) {
            $redis = $this->getClient();
            $redis->function->delete(self::LIB_NAME);
        }
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['function', []],
                ['function', 0],
            ],
            'with provided keys' => [
                ['function', ['key1', 'key2']],
                ['function', 2, 'key1', 'key2'],
            ],
            'with provided keys and arguments' => [
                ['function', ['key1', 'key2'], 'arg1', 'arg2'],
                ['function', 2, 'key1', 'key2', 'arg1', 'arg2'],
            ],
        ];
    }
}
