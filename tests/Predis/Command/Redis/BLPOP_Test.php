<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-list
 */
class BLPOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\BLPOP';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'BLPOP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key1', 'key2', 'key3', 10);
        $expected = array('key1', 'key2', 'key3', 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsKeysAsSingleArray(): void
    {
        $arguments = array(array('key1', 'key2', 'key3'), 10);
        $expected = array('key1', 'key2', 'key3', 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('key', 'value');
        $expected = array('key', 'value');

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }
}
