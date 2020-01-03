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
 * Class ZSetRemoveMaxByScoreTest
 * @package Predis\Command
 * @author Ahmed Raafat <ahmed.raafat1412@gmail.com>
 *
 * @group commands
 * @group realm-zset
 */
class ZSetRemoveMaxByScoreTest extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ZSetRemoveMaxByScore';
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId()
    {
        return 'ZPOPMAX';
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $data = array('four', '4', 'three', '3');
        $expectedResponse = array('four' => '4', 'three' => '3');
        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($data));
    }

    /**
     * @group connected
     */
    public function testDefaultPopAndReturnMaxHighestScoreMembers()
    {
        $redisClient = $this->getClient();

        $values = array(
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
        );

        $redisClient->zadd('mytestzset', $values);

        $expectedResponse = array('four' => '4');

        $this->assertSame($expectedResponse, $redisClient->zpopmax('mytestzset'));
    }

    /**
     * @group connected
     */
    public function testPopAndReturnMaxHighestScoreMembersWithCount()
    {
        $redisClient = $this->getClient();

        $values = array(
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
        );
        $count = 2;

        $redisClient->zadd('mytestzset', $values);

        $expectedResponse = array('four' => '4', 'three' => '3');

        $this->assertSame($expectedResponse, $redisClient->zpopmax('mytestzset', $count));
    }

    /**
     * @group connected
     */
    public function testPopAndReturnMaxHighestScoreMembersWithEmptyZSet()
    {
        $redisClient = $this->getClient();

        $redisClient->zadd('mytestzset', ['one' => 1]);

        $redisClient->zpopmax('mytestzset');

        $this->assertSame([], $redisClient->zpopmax('mytestzset'));
    }
}
