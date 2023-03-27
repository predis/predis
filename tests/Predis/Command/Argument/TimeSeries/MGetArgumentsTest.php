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

namespace Predis\Command\Argument\TimeSeries;

use PHPUnit\Framework\TestCase;

class MGetArgumentsTest extends TestCase
{
    /**
     * @var MGetArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new MGetArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithWithLabelsModifier(): void
    {
        $this->arguments->withLabels();

        $this->assertSame(['WITHLABELS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithSelectedLabelsModifier(): void
    {
        $this->arguments->selectedLabels('label1', 'label2');

        $this->assertSame(['SELECTED_LABELS', 'label1', 'label2'], $this->arguments->toArray());
    }
}
