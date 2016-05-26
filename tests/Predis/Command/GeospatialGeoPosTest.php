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
class GeospatialGeoPosTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\GeospatialGeoPos';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'GEOPOS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 'member:1', 'member:2');
        $expected = array('key', 'member:1', 'member:2');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithMembersAsSingleArray()
    {
        $arguments = array('key', array('member:1', 'member:2'));
        $expected = array('key', 'member:1', 'member:2');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $raw = array(
            array('13.361389338970184', '38.115556395496299'),
            array('15.087267458438873', '37.50266842333162'),
        );

        $expected = array(
            array('13.361389338970184', '38.115556395496299'),
            array('15.087267458438873', '37.50266842333162'),
        );

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandReturnsGeoPositions()
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertEquals(array(
            array('13.361389338970184', '38.115556395496299'),
            array('15.087267458438873', '37.50266842333162'),
        ), $redis->geopos('Sicily', 'Palermo', 'Catania'));
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
        $redis->geopos('Sicily', 'Palermo');
    }
}
