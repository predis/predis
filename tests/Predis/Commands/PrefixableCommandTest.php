<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 *
 */
class PrefixableCommandTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface()
    {
        $command = $this->getMockForAbstractClass('Predis\Commands\PrefixableCommand');

        $this->assertInstanceOf('Predis\Commands\IPrefixable', $command);
        $this->assertInstanceOf('Predis\Commands\ICommand', $command);
    }

    /**
     * @group disconnected
     */
    public function testAddPrefixToFirstArgument()
    {
        $command = $this->getMockForAbstractClass('Predis\Commands\PrefixableCommand');
        $command->setRawArguments(array('key', 'value'));
        $command->prefixKeys('prefix:');

        $this->assertSame(array('prefix:key', 'value'), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testDoesNotBreakOnEmptyArguments()
    {
        $command = $this->getMockForAbstractClass('Predis\Commands\PrefixableCommand');
        $command->prefixKeys('prefix:');

        $this->assertEmpty($command->getArguments());
    }
}
