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

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

class JSONMSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONMSET::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONMSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', 'value', 'key1', '$', 'value1'];
        $expected = ['key', '$..', 'value', 'key1', '$', 'value1'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
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
     * @requiresRedisJsonVersion >= 2.6.0
     */
    public function testSetMultipleJsonDocuments(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->jsonmset('doc1', '$', '{"a":2}', 'doc2', '$', '{"b":3}'));
        $this->assertEquals(['[{"a":2}]', '[{"b":3}]'], $redis->jsonmget(['doc1', 'doc2'], '$'));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisJsonVersion >= 2.6.0
     */
    public function testThrowsExceptionOnNewValuesNotInTheRootPath(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR new objects must be created at the root');

        $redis->jsonmset('doc1', '$', '{"a":2}', 'doc2', '$.f', '{"b":3}');
    }
}
