<?php

namespace Predis\Command\Argument\Search\HybridSearch;

use PHPUnit\Framework\TestCase;

class ReducerTest extends TestCase
{
    /**
     * @return void
     */
    public function testToArray(): void
    {
        $reducer = new Reducer(Reducer::REDUCE_MAX, ['prop1', 'prop2'], 'alias');
        $this->assertSame([Reducer::REDUCE_MAX, 2, 'prop1', 'prop2', 'AS', 'alias'], $reducer->toArray());
    }
}
