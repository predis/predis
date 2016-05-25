<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Profile;

use PredisTestCase;

/**
 *
 */
class FactoryTest extends PredisTestCase
{
    const DEFAULT_PROFILE_VERSION = '3.2';
    const DEVELOPMENT_PROFILE_VERSION = '3.2';

    /**
     * @group disconnected
     */
    public function testGetVersion()
    {
        $profile = Factory::get('2.0');

        $this->assertInstanceOf('Predis\Profile\ProfileInterface', $profile);
        $this->assertEquals('2.0', $profile->getVersion());
    }

    /**
     * @group disconnected
     */
    public function testGetDefault()
    {
        $profile1 = Factory::get(self::DEFAULT_PROFILE_VERSION);
        $profile2 = Factory::get('default');
        $profile3 = Factory::getDefault();

        $this->assertInstanceOf('Predis\Profile\ProfileInterface', $profile1);
        $this->assertInstanceOf('Predis\Profile\ProfileInterface', $profile2);
        $this->assertInstanceOf('Predis\Profile\ProfileInterface', $profile3);
        $this->assertEquals($profile1->getVersion(), $profile2->getVersion());
        $this->assertEquals($profile2->getVersion(), $profile3->getVersion());
    }

    /**
     * @group disconnected
     */
    public function testGetDevelopment()
    {
        $profile1 = Factory::get('dev');
        $profile2 = Factory::getDevelopment();

        $this->assertInstanceOf('Predis\Profile\ProfileInterface', $profile1);
        $this->assertInstanceOf('Predis\Profile\ProfileInterface', $profile2);
        $this->assertEquals(self::DEVELOPMENT_PROFILE_VERSION, $profile2->getVersion());
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     * @expectedExceptionMessage Unknown server profile: '1.0'.
     */
    public function testGetUndefinedProfile()
    {
        Factory::get('1.0');
    }

    /**
     * @group disconnected
     */
    public function testDefineProfile()
    {
        $profileClass = get_class($this->getMock('Predis\Profile\ProfileInterface'));

        Factory::define('mock', $profileClass);

        $this->assertInstanceOf($profileClass, Factory::get('mock'));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class 'stdClass' is not a valid profile class.
     */
    public function testDefineInvalidProfile()
    {
        Factory::define('bogus', 'stdClass');
    }
}
