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

namespace Predis\Command\Redis\Search;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTCONFIG_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTCONFIG::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTCONFIG';
    }

    /**
     * @group disconnected
     */
    public function testGetFilterArguments(): void
    {
        $arguments = ['GET', 'option'];
        $expected = ['GET', 'option'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testHelpFilterArguments(): void
    {
        $arguments = ['HELP', 'option'];
        $expected = ['HELP', 'option'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testSetFilterArguments(): void
    {
        $arguments = ['SET', 'option', 'value'];
        $expected = ['SET', 'option', 'value'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testSetGivenRediSearchConfigurationParameter(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftconfig->set('TIMEOUT', 42));
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testSetGivenRediSearchConfigurationParameterResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals('OK', $redis->ftconfig->set('TIMEOUT', 42));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testGetReturnsGivenRediSearchConfigurationParameter(): void
    {
        $redis = $this->getClient();

        $this->assertEquals([['MAXEXPANSIONS', '200']], $redis->ftconfig->get('MAXEXPANSIONS'));
        $this->assertEmpty($redis->ftconfig->get('foobar'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testGetReturnsGivenRediSearchConfigurationParameterResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals(['MAXEXPANSIONS' => '200'], $redis->ftconfig->get('MAXEXPANSIONS'));
        $this->assertEmpty($redis->ftconfig->get('foobar'));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testHelpReturnsGivenRediSearchConfigurationDescription(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [
            [
                'MAXEXPANSIONS',
                'Description',
                'Maximum prefix expansions to be used in a query',
                'Value',
                '200',
            ],
        ];

        $this->assertEquals($expectedResponse, $redis->ftconfig->help('MAXEXPANSIONS'));
        $this->assertEmpty($redis->ftconfig->help('foobar'));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testSetThrowsExceptionOnNonExistingOption(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Invalid option');

        $redis->ftconfig->set('foobar', 'value');
    }
}
