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
 * @group realm-geospatial
 */
class GEODIST_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\GEODIST';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'GEODIST';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 'member:1', 'member:2', 'km'];
        $expected = ['key', 'member:1', 'member:2', 'km'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['103.31822459492736'];
        $expected = ['103.31822459492736'];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
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
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandReturnsGeoDistance(): void
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertSame('166.2742', $redis->geodist('Sicily', 'Palermo', 'Catania', 'km'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testCommandReturnsGeoDistanceResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertSame('166.2742', $redis->geodist('Sicily', 'Palermo', 'Catania', 'km'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('Sicily', 'Palermo');
        $redis->geodist('Sicily', 'Palermo', 'Catania');
    }
}
