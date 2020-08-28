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
 * @group realm-server
 */
class SLAVEOF_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SLAVEOF';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SLAVEOF';
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsHostPortArray(): void
    {
        $arguments = array('127.0.0.1', '80');
        $expected = array('127.0.0.1', '80');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsNoOneArray(): void
    {
        $arguments = array('NO', 'ONE');
        $expected = array('NO', 'ONE');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsNoOneString(): void
    {
        $arguments = array('NO ONE');
        $expected = array('NO', 'ONE');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertTrue($this->getCommand()->parseResponse(true));
    }
}
