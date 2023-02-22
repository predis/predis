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

use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\Schema;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

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
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testAddsAttributeToExistingIndex(): void
    {
        $redis = $this->getClient();

        $schema = new Schema();
        $schema->addTextField('field_name');

        $this->assertEquals('OK', $redis->ftcreate('index', $schema));

        $schema = new Schema(true);
        $schema->addTextField('new_field_name');

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

        $redis->ftalter('alias', (new Schema())->addTextField('field_name'));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['index', (new Schema(true))->addTextField('text_field')],
                ['index', 'SCHEMA', 'ADD', 'text_field', 'TEXT'],
            ],
            'with SKIPINITIALSCAN modifier' => [
                ['index', (new Schema(true))->addTextField('text_field'), (new CreateArguments())->skipInitialScan()],
                ['index', 'SKIPINITIALSCAN', 'SCHEMA', 'ADD', 'text_field', 'TEXT'],
            ],
        ];
    }
}
