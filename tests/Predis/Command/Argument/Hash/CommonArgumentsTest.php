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

namespace Predis\Command\Argument\Hash;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class CommonArgumentsTest extends TestCase
{
    /**
     * @var CommonExpiration
     */
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class() extends CommonExpiration {};
    }

    /**
     * @group disconnected
     * @dataProvider expirationModifierProvider
     * @param  string $modifier
     * @return void
     */
    public function testSetExpirationModifier(string $modifier): void
    {
        $this->assertSame([strtoupper($modifier)], $this->testClass->setOverrideModifier($modifier)->toArray());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetExpirationThrowsExceptionOnNonExistingModifier(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Incorrect expire modifier value');

        $this->testClass->setOverrideModifier('wrong');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetExpirationThrowsExceptionOnAlreadySetModifier(): void
    {
        $this->testClass->setOverrideModifier('NX');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot be mixed with other override modifiers');

        $this->testClass->setOverrideModifier('XX');
    }

    /**
     * @group disconnected
     * @dataProvider ttlModifierProvider
     * @param  array $arguments
     * @return void
     */
    public function testSetTTLModifier(array $arguments): void
    {
        $this->assertSame($arguments, $this->testClass->setTTLModifier(...$arguments)->toArray());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetTTLExpirationThrowsExceptionOnNonExistingModifier(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Incorrect TTL modifier');

        $this->testClass->setTTLModifier('wrong', 10);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetTTLExpirationThrowsExceptionOnAlreadySetModifier(): void
    {
        $this->testClass->setTTLModifier('ex', 10);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot be mixed with other TTL modifiers');

        $this->testClass->setTTLModifier('px', 10);
    }

    public function expirationModifierProvider(): array
    {
        return [['nx'], ['xx'], ['gt'], ['lt']];
    }

    public function ttlModifierProvider(): array
    {
        return [[['EX', 10]], [['EXAT', 10]], [['PX', 10]], [['PXAT', 10]]];
    }
}
