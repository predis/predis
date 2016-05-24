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
class GeospatialGeoDistTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\GeospatialGeoDist';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'GEODIST';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 'member:1', 'member:2', 'km');
        $expected = array('key', 'member:1', 'member:2', 'km');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $raw = array('103.31822459492736');
        $expected = array('103.31822459492736');

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testCommandReturnsGeoDistance()
    {
        $redis = $this->getClient();

        $redis->geoadd('Sicily', '13.361389', '38.115556', 'Palermo', '15.087269', '37.502669', 'Catania');
        $this->assertSame('166.2742', $redis->geodist('Sicily', 'Palermo', 'Catania', 'km'));
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
        $redis->geodist('Sicily', 'Palermo', 'Catania');
    }
}
