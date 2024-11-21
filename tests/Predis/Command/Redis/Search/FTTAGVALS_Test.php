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

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\TagField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTTAGVALS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTTAGVALS::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTTAGVALS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['index', 'fieldName'];
        $expectedArguments = ['index', 'fieldName'];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
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
    public function testReturnIndexedTagFieldDistinctValues(): void
    {
        $redis = $this->getClient();
        $expectedResponse = ['hello', 'hey', 'world'];

        $this->assertEquals(
            'OK',
            $redis->ftcreate(
                'index',
                [new TagField('tag_field')],
                (new CreateArguments())->prefix(['prefix:'])
            )
        );

        $this->assertSame(
            1,
            $redis->hset('prefix:1', 'tag_field', 'Hello, World')
        );

        $this->assertSame(
            1,
            $redis->hset('prefix:2', 'tag_field', 'Hey, World')
        );

        $this->assertSame($expectedResponse, $redis->fttagvals('index', 'tag_field'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Unknown Index name');

        $redis->fttagvals('index', 'fieldName');
    }
}
