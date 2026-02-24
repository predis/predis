<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;

class FT_LIST_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FT_LIST::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FT_LIST';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $this->assertEmpty($this->getCommand()->getArguments());
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.0.0
     */
    public function testReturnListOfExistingIndices(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftcreate('idx1', [new TextField('text')]));
        $this->assertEquals('OK', $redis->ftcreate('idx2', [new TextField('text')]));

        $this->sleep(0.1);

        $this->assertSameValues(['idx1', 'idx2'], $redis->ft_list());
    }
}
