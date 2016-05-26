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
class GeospatialGeoRadiusTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\GeospatialGeoRadius';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'GEORADIUS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array(
            'Sicily', 15, 37, 200, 'km',
            'WITHCOORD', 'WITHDIST', 'WITHHASH', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist',
        );

        $expected = array(
            'Sicily', 15, 37, 200, 'km',
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
            'Sicily', 15, 37, 200, 'km', array(
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
            'Sicily', 15, 37, 200, 'km',
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
            'Sicily', 15, 37, 200, 'km', array(
                'store' => 'key:store',
                'storedist' => 'key:storedist',
                'withdist' => false,
                'withcoord' => false,
                'withhash' => false,
                'count' => 1,
                'sort' => 'asc',
            ),
        );

        $expected = array('Sicily', 15, 37, 200, 'km', 'COUNT', 1, 'ASC', 'STORE', 'key:store', 'STOREDIST', 'key:storedist');

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
            array('Palermo', '190.4424'),
            array('Catania', '56.4413'),
        );

        $expected = array(
            array('Palermo', '190.4424'),
            array('Catania', '56.4413'),
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

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertEquals(array('Palermo', 'Catania'), $redis->georadius('Sicily', 15, 37, 200, 'km'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandReturnsGeoRadiusInfoWithOptions()
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertEquals(array(
            array('Palermo', '190.4424', array('13.361389338970184', '38.115556395496299')),
            array('Catania', '56.4413', array('15.087267458438873', '37.50266842333162')),
        ), $redis->georadius('Sicily', 15, 37, 200, 'km', 'WITHDIST', 'WITHCOORD'));
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
        $redis->georadius('Sicily', 15, 37, 200, 'km');
    }
}
