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

use Predis\Command\Argument\Search\Arguments;
use Predis\Command\Argument\Search\Schema;
use Predis\Command\Redis\PredisCommandTestCase;

class FTCREATE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTCREATE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTCREATE';
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testCreatesSearchIndex(): void
    {
        $redis = $this->getClient();

        $arguments = new Arguments();
        $arguments->prefix(['prefix:', 'prefix1:']);
        $arguments->filter('@age>16');
        $arguments->stopWords(['hello', 'world']);

        $schema = new Schema();
        $schema->addTextField('first', 'fst', true, true);
        $schema->addTextField('last');
        $schema->addNumericField('age');

        $actualResponse = $redis->ftcreate('index', $arguments, $schema);

        $this->assertEquals('OK', $actualResponse);
    }
}
