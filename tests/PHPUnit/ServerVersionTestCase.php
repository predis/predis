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

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 *
 */
abstract class ServerVersionTestCase extends StandardTestCase
{
    /**
     * Returns a new instance of the tested profile.
     *
     * @return ServerProfileInterface
     */
    protected abstract function getProfileInstance();

    /**
     * Returns the expected version string for the tested profile.
     *
     * @return string Version string.
     */
    protected abstract function getExpectedVersion();

    /**
     * Returns the expected list of commands supported by the tested profile.
     *
     * @return array List of supported commands.
     */
    protected abstract function getExpectedCommands();

    /**
     * Returns the list of commands supported by the current
     * server profile.
     *
     * @param ServerProfileInterface $profile Server profile instance.
     * @return array
     */
    protected function getCommands(ServerProfileInterface $profile)
    {
        $commands = $profile->getSupportedCommands();

        return array_keys($commands);
    }

    /**
     * @group disconnected
     */
    public function testGetVersion()
    {
        $profile = $this->getProfileInstance();

        $this->assertEquals($this->getExpectedVersion(), $profile->getVersion());
    }

    /**
     * @group disconnected
     */
    public function testSupportedCommands()
    {
        $profile = $this->getProfileInstance();
        $expected = $this->getExpectedCommands();
        $commands = $this->getCommands($profile);

        $this->assertSame($expected, $commands);
    }
}
