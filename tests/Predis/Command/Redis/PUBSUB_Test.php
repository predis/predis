<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-pubsub
 */
class PUBSUB_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\PUBSUB';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PUBSUB';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['channels', 'predis:*'];
        $expected = ['channels', 'predis:*'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $response = ['predis:incoming', 'predis:outgoing'];
        $expected = ['predis:incoming', 'predis:outgoing'];

        $command = $this->getCommandWithArguments('channels', 'predis:*');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testPubsubNumsub(): void
    {
        $response = ['predis:incoming', '10', 'predis:outgoing', '8'];
        $expected = ['predis:incoming' => '10', 'predis:outgoing' => '8'];

        $command = $this->getCommandWithArguments('numsub', 'predis:incoming', 'predis:outgoing');

        $this->assertSame($expected, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testPubsubNumpat(): void
    {
        $response = 6;
        $expected = 6;

        $command = $this->getCommandWithArguments('numpat');

        $this->assertSame($expected, $command->parseResponse($response));
    }
}
