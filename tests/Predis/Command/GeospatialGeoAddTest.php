<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @group commands
 * @group realm-geospatial
 */
class GeospatialGeoAddTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\GeospatialGeoAdd';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'GEOADD';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
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
    public function testFilterArgumentsWithMembersAsSingleArray()
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
    public function testParseResponse()
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
    public function testCommandFillsSortedSet()
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo');
        $this->assertSame(array('Palermo' => '3479099956230698'), $redis->zrange('Sicily', 0, -1, 'WITHSCORES'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongType()
    {
        $redis = $this->getClient();

        $redis->lpush('Sicily', 'Palermo');
        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo');
    }
}
