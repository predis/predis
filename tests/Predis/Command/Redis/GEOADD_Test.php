<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-geospatial
 */
class GEOADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\GEOADD';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'GEOADD';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $expected = array('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithMembersAsSingleArray(): void
    {
        $arguments = array('Sicily', array(
            array('13.361389', '38.115556', 'Palermo'),
            array('15.087269', '37.502669', 'Catania'),
        ));

        $expected = array('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = 1;
        $expected = 1;

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandFillsSortedSet(): void
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo');
        $this->assertSame(array('Palermo' => '3479099956230698'), $redis->zrange('Sicily', 0, -1, 'WITHSCORES'));
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
        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo');
    }
}
