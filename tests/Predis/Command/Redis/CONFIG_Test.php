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

/**
 * @group commands
 * @group realm-server
 */
class CONFIG_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\CONFIG';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'CONFIG';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['GET', 'slowlog'];
        $expected = ['GET', 'slowlog'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponseOfConfigGet(): void
    {
        $raw = ['slowlog-log-slower-than', '10000', 'slowlog-max-len', '64', 'loglevel', 'verbose'];
        $expected = [
            'slowlog-log-slower-than' => '10000',
            'slowlog-max-len' => '64',
            'loglevel' => 'verbose',
        ];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseOfConfigSet(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseOfConfigResetstat(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsListOfConfigurationValues(): void
    {
        $redis = $this->getClient();

        $this->assertIsArray($configs = $redis->config('GET', '*'));
        $this->assertGreaterThan(1, count($configs));
        $this->assertArrayHasKey('loglevel', $configs);
        $this->assertArrayHasKey('appendonly', $configs);
        $this->assertArrayHasKey('dbfilename', $configs);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsListOfOneConfigurationEntry(): void
    {
        $redis = $this->getClient();

        $this->assertIsArray($configs = $redis->config('GET', 'dbfilename'));
        $this->assertCount(1, $configs);
        $this->assertArrayHasKey('dbfilename', $configs);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsEmptyListOnUnknownConfigurationEntry(): void
    {
        $redis = $this->getClient();

        $this->assertSame([], $redis->config('GET', 'foobar'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsTrueOnSuccessfulConfiguration(): void
    {
        $redis = $this->getClient();

        $previous = $redis->config('GET', 'loglevel');

        $this->assertEquals('OK', $redis->config('SET', 'loglevel', 'notice'));
        $this->assertSame(['loglevel' => 'notice'], $redis->config('GET', 'loglevel'));

        // We set the loglevel configuration to the previous value.
        $redis->config('SET', 'loglevel', $previous['loglevel']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionWhenSettingUnknownConfiguration(): void
    {
        $this->expectException('Predis\Response\ServerException');
        if ($this->isRedisServerVersion('<=', '6.0')) {
            $this->expectExceptionMessage('ERR Unsupported CONFIG parameter: foo');
        }

        if ($this->isRedisServerVersion('>=', '7.0')) {
            $this->expectExceptionMessage("ERR Unknown option or number of arguments for CONFIG SET - 'foo'");
        }

        $redis = $this->getClient();

        $redis->config('SET', 'foo', 'bar');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testReturnsTrueOnResetstat(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->config('RESETSTAT'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnUnknownSubcommand(): void
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();

        $redis->config('FOO');
    }
}
