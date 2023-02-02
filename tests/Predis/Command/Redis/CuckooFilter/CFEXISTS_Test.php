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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;

class CFEXISTS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFEXISTS::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFEXISTS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 'item'];
        $expectedArguments = ['key', 'item'];

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
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testExistsReturnsExistingItemWithinCuckooFilter(): void
    {
        $redis = $this->getClient();

        $redis->cfAdd('key', 'item');

        $this->assertSame(1, $redis->cfExists('key', 'item'));
        $this->assertSame(0, $redis->cfExists('non-existing key', 'item'));
    }
}
