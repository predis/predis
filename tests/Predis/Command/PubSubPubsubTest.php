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
 * @group realm-pubsub
 */
class PubSubPubsubTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\PubSubPubsub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'PUBSUB';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('channels', 'predis:*');
        $expected = array('channels', 'predis:*');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $response = array('predis:incoming', 'predis:outgoing');
        $expected = array('predis:incoming', 'predis:outgoing');

        $command = $this->getCommandWithArguments('channels', 'predis:*');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testPubsubNumsub()
    {
        $response = array('predis:incoming', '10', 'predis:outgoing', '8');
        $expected = array('predis:incoming' => '10', 'predis:outgoing' => '8');

        $command = $this->getCommandWithArguments('numsub', 'predis:incoming', 'predis:outgoing');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testPubsubNumpat()
    {
        $response = 6;
        $expected = 6;

        $command = $this->getCommandWithArguments('numpat');

        $this->assertSame($expected, $command->parseResponse($response));
    }
}
