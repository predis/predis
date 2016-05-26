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
class GeospatialGeoRadiusByMemberTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\GeospatialGeoRadiusByMember';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'GEORADIUSBYMEMBER';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array(
            'Sicily', 'Agrigento', 100, 'km',
            'WITHCOORD', 'WITHDIST', 'WITHHASH', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist',
        );

        $expected = array(
            'Sicily', 'Agrigento', 100, 'km',
            'WITHCOORD', 'WITHDIST', 'WITHHASH', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist',
        );

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithComplexOptions()
    {
        $arguments = array(
            'Sicily', 'Agrigento', 100, 'km', array(
                'store' => 'key:store',
                'storedist' => 'key:storedist',
                'withdist' => true,
                'withcoord' => true,
                'withhash' => true,
                'count' => 1,
                'sort' => 'asc',
            ),
        );

        $expected = array(
            'Sicily', 'Agrigento', 100, 'km',
            'WITHCOORD', 'WITHDIST', 'WITHHASH', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist',
        );

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithSpecificOptionsSetToFalse()
    {
        $arguments = array(
            'Sicily', 'Agrigento', 100, 'km', array(
                'store' => 'key:store',
                'storedist' => 'key:storedist',
                'withdist' => false,
                'withcoord' => false,
                'withhash' => false,
                'count' => 1,
                'sort' => 'asc',
            ),
        );

        $expected = array('Sicily', 'Agrigento', 100, 'km', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponseWithNoOptions()
    {
        $raw = array(
            array('Agrigento', 'Palermo'),
        );

        $expected = array(
            array('Agrigento', 'Palermo'),
        );

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandReturnsGeoRadiusInfoWithNoOptions()
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania', '13.583333', '37.316667', 'Agrigento');
        $this->assertEquals(array('Agrigento', 'Palermo'), $redis->georadiusbymember('Sicily', 'Agrigento', 100, 'km'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandReturnsGeoRadiusInfoWithOptions()
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania', '13.583333', '37.316667', 'Agrigento');
        $this->assertEquals(array(
            array('Agrigento', '0.0000', array('13.5833314061164856', '37.31666804993816555')),
            array('Palermo', '90.9778', array('13.361389338970184', '38.115556395496299')),
        ), $redis->georadiusbymember('Sicily', 'Agrigento', 100, 'km', 'WITHDIST', 'WITHCOORD'));
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
        $redis->georadiusbymember('Sicily', 'Agrigento', 200, 'km');
    }
}
