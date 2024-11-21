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

use Predis\Command\Argument\Search\AlterArguments;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTALTER_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTALTER::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTALTER';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
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
    public function testAddsAttributeToExistingIndex(): void
    {
        $redis = $this->getClient();

        $schema = [new TextField('field_name')];

        $this->assertEquals('OK', $redis->ftcreate('index', $schema));

        $schema = [new TextField('new_field_name')];

        $this->assertEquals('OK', $redis->ftalter('index', $schema));
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
        $this->expectExceptionMessage('Unknown index name');

        $redis->ftalter('alias', [new TextField('field_name')]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['index', [new TextField('text_field')]],
                ['index', 'SCHEMA', 'ADD', 'text_field', 'TEXT'],
            ],
            'with SKIPINITIALSCAN modifier' => [
                ['index', [new TextField('text_field')], (new AlterArguments())->skipInitialScan()],
                ['index', 'SKIPINITIALSCAN', 'SCHEMA', 'ADD', 'text_field', 'TEXT'],
            ],
        ];
    }
}
