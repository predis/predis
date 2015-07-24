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
 * @group realm-list
 */
class ListPopLastPushHeadBlockingTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ListPopLastPushHeadBlocking';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'BRPOPLPUSH';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key:source', 'key:destination', 10);
        $expected = array('key:source', 'key:destination', 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $this->assertSame('element', $this->getCommand()->parseResponse('element'));
    }
}
