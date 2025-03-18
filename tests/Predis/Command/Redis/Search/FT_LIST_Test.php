<?php

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
